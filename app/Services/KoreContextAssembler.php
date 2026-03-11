<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Assembles the full AI context for each chat turn.
 *
 * On every message, three layers of context are injected into the system prompt:
 *
 *   1. STATIC — firm identity, current user, today's date (always present)
 *   2. STRUCTURAL — live ERP data fetched based on entities detected in the message
 *      - Project numbers (\d{4}-\d{2,6}) → full project summary + comms + invoices
 *      - Proposal refs (P\d{4}-\d{3,4}) → proposal + fee worksheet
 *      - Keywords ("overdue", "my tasks", etc.) → specialised data fetches
 *      - If no entity detected → portfolio overview + user's tasks
 *   3. SEMANTIC — RAG vector search on indexed documents for the user's query
 *      (top 3 chunks from pgvector; skipped gracefully if indexing hasn't run)
 *
 * Context is capped so the total system prompt + history fits in the model's
 * context window (configured via ai_context_window system setting).
 */
class KoreContextAssembler
{
    public function __construct(
        private readonly KoreDataTools             $dataTools,
        private readonly ProjectContextSearchService $searchService,
    ) {}

    // ── System Prompt ──────────────────────────────────────────────────────────

    /**
     * Build the static system prompt header.
     * Called once per conversation; defines Kore AI's identity and guidelines.
     */
    public function buildSystemPrompt(User $user): string
    {
        $firmName = SystemSetting::get('company_name', config('kore.company_name', 'the firm'));
        $today    = now()->format(config('kore.date_format', 'M d, Y'));
        $currency = config('kore.currency_symbol', '$');
        $roleName = $user->role?->name ?? 'Staff';

        return <<<PROMPT
You are Kore AI, the intelligent assistant for {$firmName}'s project management system (Kore ERP).

══════════════════════════════════════════════════════════
CRITICAL RULE — NO HALLUCINATION
══════════════════════════════════════════════════════════
The CONTEXT block appended below is a LIVE EXPORT directly from the database.
It is the COMPLETE and AUTHORITATIVE source of truth for this conversation.

YOU MUST FOLLOW THESE RULES WITHOUT EXCEPTION:
1. ONLY state facts, names, numbers, and records that are explicitly present in the CONTEXT block.
2. If something is NOT in the CONTEXT block, it does NOT exist in the system. Say so plainly.
3. NEVER invent, estimate, fabricate, or assume any project names, proposal IDs, client names, dollar amounts, or counts that are not in the CONTEXT.
4. If the database is empty (e.g. "Active Projects: 0"), respond with that truth — do not make up example data.
5. Do not say "I found something" or "I can see…" unless the data is literally present in the CONTEXT block below.
6. When the context shows zero records, your answer must reflect zero records — no exceptions.
══════════════════════════════════════════════════════════

YOUR CAPABILITIES (when data exists in the database):
- Companies: full client/vendor list with sector, region, contact counts, project counts
- Contacts: names, titles, emails, phone numbers — searchable by company
- Projects: budgets, phases, team assignments, percent-complete, schedule, documents, task breakdowns
- Programs: multi-site rollout containers with budget rollups across all site projects
- Proposals: pipeline by status, fee worksheets, scope of work
- Approvals: pending approvals by type (timesheet, time-off, expense) for the current user or firm-wide
- Time Off / PTO: balances, used hours, upcoming approved/pending requests, team calendar
- Expenses: pending expense requests, project expense summaries by category
- Invoices: global invoice dashboard, project-specific billing history, overdue tracking
- Schedule of Fees: current billing/cost rates per role
- Timesheets: personal summary, firm-wide utilization rates, submission status
- Tasks: my assigned tasks, overdue tasks, project-level task breakdowns by status
- Holidays: upcoming public holidays
- Client communications: inbound email on projects
- Indexed project documents: RAG-powered semantic search

RESPONSE GUIDELINES:
- Lead with the direct answer. Then provide supporting detail.
- Use specific numbers from the context. Never estimate when you have actual data.
- Flag risks proactively: budget overruns, overdue phases, unsigned proposals, overdue invoices.
- Format with markdown: **bold** key metrics, use tables for comparisons.
- If data is absent from the context, say "There are no [X] in the system" — not "I couldn't find any."

CURRENCY: {$currency} | TODAY: {$today}
CURRENT USER: {$user->full_name} | ROLE: {$roleName}
PROMPT;
    }

    // ── Dynamic Context Assembly ───────────────────────────────────────────────

    /**
     * Assemble live ERP context for a user message.
     *
     * Returns:
     *   'context'  string  — formatted context block to append to system prompt
     *   'sources'  array   — list of sources for the UI "Sources" panel
     */
    public function assembleContext(string $userMessage, User $user, ?int $scopedProjectId = null): array
    {
        $contextBlocks = [];
        $sources       = [];

        // ── 1. Scope-pinned project (conversation is scoped to a specific project) ──
        if ($scopedProjectId) {
            $contextBlocks[] = $this->dataTools->getProjectSummary($scopedProjectId);
            $contextBlocks[] = $this->dataTools->getProjectCommunications($scopedProjectId, 3);
            $contextBlocks[] = $this->dataTools->getProjectTimesheetBreakdown($scopedProjectId);
            $contextBlocks[] = $this->dataTools->getProjectInvoices($scopedProjectId);
            $sources[]       = ['type' => 'project', 'label' => "Project #{$scopedProjectId}"];
        }

        // ── 2. Entity detection — projects mentioned in the message ──────────────
        $mentionedProjects = $this->detectProjects($userMessage);
        foreach ($mentionedProjects as $project) {
            if ($project->id === $scopedProjectId) continue; // already included above
            $contextBlocks[] = $this->dataTools->getProjectSummary($project->id);
            $contextBlocks[] = $this->dataTools->getProjectCommunications($project->id, 3);
            $sources[]       = ['type' => 'project', 'label' => $project->project_number . ' ' . $project->title];
        }

        // ── 3. Entity detection — proposals mentioned ─────────────────────────────
        $mentionedProposals = $this->detectProposals($userMessage);
        foreach ($mentionedProposals as $proposal) {
            $contextBlocks[] = $this->dataTools->getProposalSummary($proposal->id);
            $sources[]       = ['type' => 'proposal', 'label' => $proposal->ref . ' ' . $proposal->title];
        }

        // ── 4. Keyword-based intent detection ────────────────────────────────────
        $lowerMsg = mb_strtolower($userMessage);

        // Proposals
        if ($this->containsAny($lowerMsg, ['proposal', 'proposals', 'quote', 'pipeline', 'open proposal', 'approved proposal', 'pending proposal'])) {
            if (empty($mentionedProposals) && ! $scopedProjectId) {
                $contextBlocks[] = $this->dataTools->getProposalsPipelineSummary();
                $sources[]       = ['type' => 'proposals', 'label' => 'Proposals pipeline'];
            }
        }

        // Companies
        if ($this->containsAny($lowerMsg, ['compan', 'client', 'vendor', 'customer', 'who do we work with', 'our clients', 'our companies'])) {
            $namedCompany = $this->detectCompany($userMessage);
            if ($namedCompany) {
                $contextBlocks[] = $this->dataTools->getCompanyContacts($namedCompany->id);
                $sources[]       = ['type' => 'company', 'label' => $namedCompany->name . ' contacts'];
            } else {
                $contextBlocks[] = $this->dataTools->getCompaniesSummary();
                $sources[]       = ['type' => 'companies', 'label' => 'Companies list'];
            }
        }

        // Contacts
        if ($this->containsAny($lowerMsg, ['contact', 'contacts', 'person', 'people', 'who is', 'who works at', 'reach out', 'email address', 'phone number'])) {
            $namedCompany = $this->detectCompany($userMessage);
            if ($namedCompany) {
                if (! $this->containsAny($lowerMsg, ['compan', 'client', 'vendor', 'customer'])) {
                    $contextBlocks[] = $this->dataTools->getCompanyContacts($namedCompany->id);
                    $sources[]       = ['type' => 'company', 'label' => $namedCompany->name . ' contacts'];
                }
            } else {
                $contextBlocks[] = $this->dataTools->getCompanyContacts();
                $sources[]       = ['type' => 'contacts', 'label' => 'Contacts list'];
            }
        }

        // My tasks
        if ($this->containsAny($lowerMsg, ['my task', 'my work', 'assigned to me', 'what do i have', 'my deadline'])) {
            $contextBlocks[] = $this->dataTools->getUserTasks($user);
            $sources[]       = ['type' => 'tasks', 'label' => 'My assigned tasks'];
        }

        // Overdue tasks
        if ($this->containsAny($lowerMsg, ['overdue', 'late', 'past due', 'behind schedule'])) {
            $contextBlocks[] = $this->dataTools->getUserTasks($user, overdueOnly: true);
        }

        // Project-level task breakdown
        if ($this->containsAny($lowerMsg, ['task', 'tasks', 'to-do', 'todo', 'work items', 'blocked']) && ! empty($mentionedProjects)) {
            foreach ($mentionedProjects as $p) {
                $contextBlocks[] = $this->dataTools->getProjectTaskSummary($p->id);
                $sources[]       = ['type' => 'tasks', 'label' => $p->project_number . ' tasks'];
            }
        }
        if ($this->containsAny($lowerMsg, ['task', 'tasks', 'to-do', 'work items', 'blocked']) && $scopedProjectId) {
            $contextBlocks[] = $this->dataTools->getProjectTaskSummary($scopedProjectId);
            $sources[]       = ['type' => 'tasks', 'label' => "Project #{$scopedProjectId} tasks"];
        }

        // Timesheets
        if ($this->containsAny($lowerMsg, ['my timesheet', 'my hours', 'hours logged', 'hours worked', 'my time'])) {
            $contextBlocks[] = $this->dataTools->getUserTimesheetSummary($user);
            $sources[]       = ['type' => 'timesheets', 'label' => 'My timesheet data'];
        }

        // Utilization
        if ($this->containsAny($lowerMsg, ['utilization', 'utilisation', 'billable hours', 'firm hours', 'staff hours', 'team hours', 'who logged', 'logged time'])) {
            $contextBlocks[] = $this->dataTools->getFirmUtilizationSummary();
            $sources[]       = ['type' => 'utilization', 'label' => 'Firm utilization'];
        }

        // Timesheet submission
        if ($this->containsAny($lowerMsg, ["hasn't submitted", "not submitted", 'timesheet status', 'missing timesheet', 'timesheet submission'])) {
            $contextBlocks[] = $this->dataTools->getTimesheetSubmissionStatus();
            $sources[]       = ['type' => 'timesheets', 'label' => 'Timesheet submission status'];
        }

        // Approvals
        if ($this->containsAny($lowerMsg, ['approval', 'approve', 'pending approval', 'waiting for approval', 'needs approval', 'review request', 'approve timesheet', 'approve expense', 'approve time off'])) {
            $contextBlocks[] = $this->dataTools->getPendingApprovals($user);
            $sources[]       = ['type' => 'approvals', 'label' => 'Pending approvals'];
        }

        // PTO / Time Off
        if ($this->containsAny($lowerMsg, ['pto', 'time off', 'time-off', 'vacation', 'sick day', 'sick leave', 'personal day', 'leave', 'day off', 'days off', 'pto balance', 'my leave'])) {
            $contextBlocks[] = $this->dataTools->getUserPtoSummary($user);
            $sources[]       = ['type' => 'pto', 'label' => 'My PTO summary'];
        }

        if ($this->containsAny($lowerMsg, ['who is out', "who's out", 'team schedule', 'team time off', 'upcoming time off', 'out of office', 'away', 'team leave'])) {
            $contextBlocks[] = $this->dataTools->getTeamTimeOffCalendar();
            $sources[]       = ['type' => 'pto', 'label' => 'Team time-off calendar'];
        }

        if ($this->containsAny($lowerMsg, ['pending time off', 'pending leave', 'time off requests', 'leave requests'])) {
            $contextBlocks[] = $this->dataTools->getPendingTimeOffRequests();
            $sources[]       = ['type' => 'pto', 'label' => 'Pending time-off requests'];
        }

        // Expenses
        if ($this->containsAny($lowerMsg, ['expense', 'expenses', 'reimbursement', 'receipt', 'spending'])) {
            if (! empty($mentionedProjects)) {
                foreach ($mentionedProjects as $p) {
                    $contextBlocks[] = $this->dataTools->getProjectExpenses($p->id);
                    $sources[]       = ['type' => 'expenses', 'label' => $p->project_number . ' expenses'];
                }
            } else {
                $contextBlocks[] = $this->dataTools->getPendingExpenses();
                $sources[]       = ['type' => 'expenses', 'label' => 'Pending expenses'];
            }
        }

        // Programs
        if ($this->containsAny($lowerMsg, ['program', 'programs', 'rollout', 'portfolio program', 'program summary', 'all sites'])) {
            $contextBlocks[] = $this->dataTools->getActivePrograms();
            $sources[]       = ['type' => 'programs', 'label' => 'Programs overview'];
        }

        // Schedule of fees / billing rates
        if ($this->containsAny($lowerMsg, ['billing rate', 'hourly rate', 'rates', 'schedule of fees', 'fee schedule', 'how much do we charge', 'rate card', 'cost rate'])) {
            $contextBlocks[] = $this->dataTools->getScheduleOfFees();
            $sources[]       = ['type' => 'fees', 'label' => 'Schedule of fees'];
        }

        // Documents
        if ($this->containsAny($lowerMsg, ['document', 'documents', 'file', 'files', 'attachment', 'uploaded', 'on file']) && (! empty($mentionedProjects) || $scopedProjectId)) {
            $projectIds = array_map(fn($p) => $p->id, $mentionedProjects);
            if ($scopedProjectId) $projectIds[] = $scopedProjectId;
            foreach (array_unique($projectIds) as $pid) {
                $contextBlocks[] = $this->dataTools->getProjectDocumentSummary($pid);
                $sources[]       = ['type' => 'documents', 'label' => "Project #{$pid} documents"];
            }
        }

        // Holidays
        if ($this->containsAny($lowerMsg, ['holiday', 'holidays', 'public holiday', 'working days', 'days off', 'next holiday', 'long weekend'])) {
            $contextBlocks[] = $this->dataTools->getUpcomingHolidays();
            $sources[]       = ['type' => 'holidays', 'label' => 'Upcoming holidays'];
        }

        // Invoices — global dashboard or project-specific overdue
        if ($this->containsAny($lowerMsg, ['invoice', 'billing', 'payment', 'outstanding', 'accounts receivable', 'ar ', 'unpaid', 'overdue invoice'])) {
            if (empty($mentionedProjects) && ! $scopedProjectId) {
                $contextBlocks[] = $this->dataTools->getGlobalInvoiceSummary();
                $sources[]       = ['type' => 'invoices', 'label' => 'Invoice dashboard'];
            }
        }

        // Fee / project finances when "fee" is mentioned without proposals
        if ($this->containsAny($lowerMsg, ['fee', 'budget', 'financ', 'cost']) && empty($mentionedProposals) && empty($mentionedProjects) && ! $scopedProjectId) {
            $contextBlocks[] = $this->dataTools->getProposalsPipelineSummary();
            $sources[]       = ['type' => 'proposals', 'label' => 'Proposals pipeline'];
        }

        // ── 5. Portfolio overview when no specific entity detected ─────────────────
        if (empty($contextBlocks)) {
            $contextBlocks[] = $this->dataTools->getPortfolioOverview($user);
            $sources[]       = ['type' => 'portfolio', 'label' => 'Portfolio overview'];
        }

        // ── 6. RAG semantic search — document content ─────────────────────────────
        try {
            $ragResults = $this->searchService->search($userMessage, $scopedProjectId, limit: 3);

            if ($ragResults->isNotEmpty()) {
                $ragBlock  = "RELEVANT DOCUMENT EXCERPTS (from indexed project files):\n";
                $ragBlock .= "---\n";

                foreach ($ragResults as $i => $result) {
                    $sim      = round($result->similarity * 100, 1);
                    $ragBlock .= "[Source " . ($i + 1) . ": {$result->source_label} — {$sim}% match]\n";
                    $ragBlock .= mb_substr($result->chunk_text, 0, 500) . "\n---\n";
                    $sources[] = ['type' => 'document', 'label' => $result->source_label];
                }

                $contextBlocks[] = $ragBlock;
            }
        } catch (\Throwable $e) {
            // RAG unavailable (Ollama not running, no indexed docs) — skip silently
        }

        $context = implode("\n\n" . str_repeat('─', 60) . "\n\n", $contextBlocks);

        return ['context' => $context, 'sources' => $sources];
    }

    // ── Entity Detection ───────────────────────────────────────────────────────

    /**
     * Extract project_number tokens from the message and look them up in the DB.
     *
     * @return Project[]
     */
    private function detectProjects(string $message): array
    {
        preg_match_all('/\b(\d{4}-\d{2,6})\b/', $message, $matches);

        if (empty($matches[1])) {
            return [];
        }

        return Project::whereIn('project_number', array_unique($matches[1]))
            ->limit(3) // cap context size
            ->get()
            ->all();
    }

    /**
     * Extract proposal refs (P2025-001) from the message.
     *
     * @return Proposal[]
     */
    private function detectProposals(string $message): array
    {
        // Matches: P2025-001, p2025-0042 (case insensitive)
        preg_match_all('/\bP(\d{4}-\d{3,4})\b/i', $message, $matches);

        if (empty($matches[1])) {
            return [];
        }

        // Reconstruct the ref format as stored: year and proposal_number
        $proposals = [];
        foreach ($matches[1] as $ref) {
            [$year, $num] = explode('-', $ref);
            $proposal = Proposal::where('year', $year)
                ->where('proposal_number', (int) $num)
                ->first();
            if ($proposal) {
                $proposals[] = $proposal;
            }
        }

        return $proposals;
    }

    /**
     * Try to match a company name from the message against all companies in the DB.
     * Uses case-insensitive substring matching so "ford" matches "Ford Motor Company".
     */
    private function detectCompany(string $message): ?Company
    {
        $companies = Company::orderByDesc(DB::raw('LENGTH(name)'))->get(['id', 'name']);

        $lower = mb_strtolower($message);
        foreach ($companies as $company) {
            if (str_contains($lower, mb_strtolower($company->name))) {
                return $company;
            }
            // Also match on first significant word (e.g. "Ford" → "Ford Motor Company")
            $firstWord = mb_strtolower(explode(' ', trim($company->name))[0]);
            if (strlen($firstWord) > 3 && str_contains($lower, $firstWord)) {
                return $company;
            }
        }

        return null;
    }

    private function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }
        return false;
    }
}
