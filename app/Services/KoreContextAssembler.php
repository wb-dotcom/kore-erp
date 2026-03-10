<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Proposal;
use App\Models\SystemSetting;
use App\Models\User;

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

        return <<<PROMPT
You are Kore AI, the intelligent assistant for {$firmName}'s project management system (Kore ERP).

YOUR CAPABILITIES:
- Full read access to all projects: budgets, phases, team assignments, percent-complete, schedule
- Access to proposals and fee worksheets (hourly rates, deliverables, milestones)
- Client communications captured from email (Postmark inbound)
- Indexed project documents (PDFs, drawings, reports, specifications)
- Timesheet and labor burn data by role, phase, and staff member
- Invoice history and payment status
- Tasks, deliverables, and milestone tracking

RESPONSE GUIDELINES:
- Lead with the direct answer. Then provide supporting detail.
- Use specific numbers from the context. Never estimate when you have actual data.
- Flag risks proactively: budget overruns (high % hours with low % completion), overdue phases, unsigned proposals, overdue invoices.
- Format with markdown: **bold** key metrics, use bullet lists for multiple items, tables for comparisons.
- When referencing a document or email, name the specific source.
- If asked for a recommendation, ground it in the actual data from the context below.
- If the data you need isn't in the context, say so clearly — do not invent figures.
- For analysis requests, compare actual vs. planned and identify the delta.

CURRENCY: {$currency} | DATE FORMAT: {$today} (this is today's date)
CURRENT USER: {$user->full_name} | ROLE: {$user->role ?? 'Staff'}
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

        if ($this->containsAny($lowerMsg, ['my task', 'my work', 'assigned to me', 'what do i have', 'my deadline'])) {
            $contextBlocks[] = $this->dataTools->getUserTasks($user);
            $sources[]       = ['type' => 'tasks', 'label' => 'My assigned tasks'];
        }

        if ($this->containsAny($lowerMsg, ['overdue', 'late', 'past due', 'behind schedule'])) {
            $contextBlocks[] = $this->dataTools->getUserTasks($user, overdueOnly: true);
        }

        if ($this->containsAny($lowerMsg, ['timesheet', 'hours logged', 'hours worked', 'my time', 'utilization'])) {
            $contextBlocks[] = $this->dataTools->getUserTimesheetSummary($user);
            $sources[]       = ['type' => 'timesheets', 'label' => 'Timesheet data'];
        }

        if ($this->containsAny($lowerMsg, ['invoice', 'billing', 'payment', 'outstanding', 'accounts receivable'])) {
            // Global invoice summary when not project-specific
            if (empty($mentionedProjects) && ! $scopedProjectId) {
                $overdue = \App\Models\Invoice::where('status', 'overdue')
                    ->with('project')
                    ->orderByDesc('total_amount')
                    ->limit(10)
                    ->get();

                if ($overdue->isNotEmpty()) {
                    $lines = ["OVERDUE INVOICES ({$overdue->count()}):"];
                    foreach ($overdue as $inv) {
                        $lines[] = "  🚨 {$inv->invoice_number}: {$inv->project?->project_number} — "
                            . config('kore.currency_symbol', '$') . number_format($inv->total_amount)
                            . " due {$inv->due_date?->format(config('kore.date_format', 'M d, Y'))}";
                    }
                    $contextBlocks[] = implode("\n", $lines);
                    $sources[]       = ['type' => 'invoices', 'label' => 'Overdue invoices'];
                }
            }
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
