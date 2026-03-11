<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ActivityTemplate;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Program;
use App\Models\ProjectType;
use App\Models\Proposal;
use App\Models\ProposalStatus;
use App\Models\Sector;
use App\Models\User;
use App\Models\WorkType;
use App\Services\ProposalSimilarityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProposalController extends Controller
{
    public function index(Request $request)
    {
        $query = Proposal::with(['company', 'status', 'accountManager', 'sector', 'workType'])
            ->orderByDesc('year')
            ->orderByDesc('proposal_number');

        // Filters
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('company', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        if ($statusId = $request->input('status_id')) {
            $query->where('status_id', $statusId);
        }

        if ($year = $request->input('year')) {
            $query->where('year', $year);
        }

        $proposals = $query->paginate(25)->withQueryString();
        $statuses  = ProposalStatus::orderBy('name')->get();
        $years     = Proposal::select('year')->distinct()->orderByDesc('year')->pluck('year');

        return view('proposals.index', compact('proposals', 'statuses', 'years'));
    }

    public function create()
    {
        $companies    = Company::where('is_active', 1)->orderBy('name')->get();
        $statuses     = ProposalStatus::orderBy('name')->get();
        $sectors      = Sector::orderBy('name')->get();
        $workTypes    = WorkType::orderBy('name')->get();
        $managers     = User::where('is_active', 1)->orderBy('first_name')->get();
        $projectTypes = ProjectType::orderBy('name')->get();
        $programs     = Program::where('status', 'active')->orderBy('name')->get();
        $contacts     = Contact::where('is_active', 1)->orderBy('first_name')->get();

        // Auto-generate next proposal number for current year
        $year       = now()->year;
        $lastNumber = Proposal::where('year', $year)->max('proposal_number') ?? 0;
        $nextNumber = $lastNumber + 1;

        return view('proposals.create', compact(
            'companies', 'statuses', 'sectors', 'workTypes', 'managers',
            'projectTypes', 'programs', 'contacts', 'year', 'nextNumber'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'year'                  => ['required', 'integer', 'min:2000'],
            'proposal_number'       => ['required', 'integer', 'min:1'],
            'title'                 => ['required', 'string', 'max:255'],
            'company_id'            => ['nullable', 'exists:companies,id'],
            'contact_id'            => ['nullable', 'exists:contacts,id'],
            'sector_id'             => ['nullable', 'exists:sectors,id'],
            'work_type_id'          => ['nullable', 'exists:work_types,id'],
            'project_type_id'       => ['nullable', 'exists:project_types,id'],
            'account_manager_id'    => ['nullable', 'exists:users,id'],
            'status_id'             => ['required', 'exists:proposal_statuses,id'],
            'po_number'             => ['nullable', 'string', 'max:100'],
            'vendor_code'           => ['nullable', 'string', 'max:50'],
            'description'           => ['nullable', 'string'],
            'submitted_date'        => ['nullable', 'date'],
            'approved_date'         => ['nullable', 'date'],
            'expiry_date'           => ['nullable', 'date'],
            'notes'                 => ['nullable', 'string'],
            'billing_type'          => ['nullable', 'in:fixed,time_and_material,hybrid,retainer,per_deliverable'],
            'billing_cycle'         => ['nullable', 'in:biweekly,monthly,quarterly,on_completion,custom'],
            'payment_terms_days'    => ['nullable', 'integer', 'min:0', 'max:365'],
            'program_id'            => ['nullable', 'exists:programs,id'],
            'contract_value'        => ['nullable', 'numeric', 'min:0'],
            'expenses_reserve'      => ['nullable', 'numeric', 'min:0'],
            'google_doc_url'        => ['nullable', 'string', 'max:500'],
            'executive_summary'     => ['nullable', 'string'],
            'scope_of_work'         => ['nullable', 'string'],
            'terms_and_conditions'  => ['nullable', 'string'],
        ]);

        $data['created_by'] = auth()->id();

        $proposal = Proposal::create($data);

        ActivityLog::record('Created proposal', 'proposals', $proposal->id, $proposal->title);

        return redirect()->route('proposals.show', $proposal)
            ->with('success', "Proposal {$proposal->ref} created successfully.");
    }

    public function show(Proposal $proposal)
    {
        $proposal->load([
            'company', 'contact', 'status', 'sector', 'workType', 'projectType',
            'accountManager', 'createdBy', 'project', 'rateSchedules',
            'program', 'billingSchedule.periods',
            'deliverables.activities.tasks',
        ]);
        $activityTemplates = ActivityTemplate::orderBy('name')->get();
        return view('proposals.show', compact('proposal', 'activityTemplates'));
    }

    public function edit(Proposal $proposal)
    {
        $companies    = Company::where('is_active', 1)->orderBy('name')->get();
        $statuses     = ProposalStatus::orderBy('name')->get();
        $sectors      = Sector::orderBy('name')->get();
        $workTypes    = WorkType::orderBy('name')->get();
        $managers     = User::where('is_active', 1)->orderBy('first_name')->get();
        $projectTypes = ProjectType::orderBy('name')->get();
        $programs     = Program::where('status', 'active')->orderBy('name')->get();
        $contacts     = Contact::where('is_active', 1)->orderBy('first_name')->get();

        return view('proposals.edit', compact(
            'proposal', 'companies', 'statuses', 'sectors', 'workTypes', 'managers',
            'projectTypes', 'programs', 'contacts'
        ));
    }

    public function update(Request $request, Proposal $proposal)
    {
        $data = $request->validate([
            'year'                  => ['required', 'integer', 'min:2000'],
            'proposal_number'       => ['required', 'integer', 'min:1'],
            'title'                 => ['required', 'string', 'max:255'],
            'company_id'            => ['nullable', 'exists:companies,id'],
            'contact_id'            => ['nullable', 'exists:contacts,id'],
            'sector_id'             => ['nullable', 'exists:sectors,id'],
            'work_type_id'          => ['nullable', 'exists:work_types,id'],
            'project_type_id'       => ['nullable', 'exists:project_types,id'],
            'account_manager_id'    => ['nullable', 'exists:users,id'],
            'status_id'             => ['required', 'exists:proposal_statuses,id'],
            'po_number'             => ['nullable', 'string', 'max:100'],
            'vendor_code'           => ['nullable', 'string', 'max:50'],
            'description'           => ['nullable', 'string'],
            'submitted_date'        => ['nullable', 'date'],
            'approved_date'         => ['nullable', 'date'],
            'expiry_date'           => ['nullable', 'date'],
            'notes'                 => ['nullable', 'string'],
            'billing_type'          => ['nullable', 'in:fixed,time_and_material,hybrid,retainer,per_deliverable'],
            'billing_cycle'         => ['nullable', 'in:biweekly,monthly,quarterly,on_completion,custom'],
            'payment_terms_days'    => ['nullable', 'integer', 'min:0', 'max:365'],
            'program_id'            => ['nullable', 'exists:programs,id'],
            'contract_value'        => ['nullable', 'numeric', 'min:0'],
            'expenses_reserve'      => ['nullable', 'numeric', 'min:0'],
            'google_doc_url'        => ['nullable', 'string', 'max:500'],
            'executive_summary'     => ['nullable', 'string'],
            'scope_of_work'         => ['nullable', 'string'],
            'terms_and_conditions'  => ['nullable', 'string'],
        ]);

        $proposal->update($data);

        ActivityLog::record('Updated proposal', 'proposals', $proposal->id, $proposal->title);

        return redirect()->route('proposals.show', $proposal)
            ->with('success', "Proposal {$proposal->ref} updated successfully.");
    }

    /**
     * GET /proposals/{proposal}/similar
     * Returns JSON: list of similar prior proposals with similarity scores and deliverable overlap.
     */
    public function similar(Proposal $proposal, ProposalSimilarityService $similarity): JsonResponse
    {
        $proposal->load(['company', 'sector', 'workType', 'projectType', 'deliverables']);

        $results = $similarity->findSimilar($proposal);

        return response()->json([
            'similar' => $results->map(fn ($r) => [
                'id'                  => $r['proposal']->id,
                'ref'                 => $r['proposal']->ref,
                'title'               => $r['proposal']->title,
                'company'             => $r['proposal']->company?->name,
                'sector'              => $r['proposal']->sector?->name,
                'work_type'           => $r['proposal']->workType?->name,
                'status'              => $r['proposal']->status?->name,
                'year'                => $r['proposal']->year,
                'contract_value'      => $r['proposal']->contract_value ?? $r['proposal']->total_fee,
                'deliverable_count'   => $r['proposal']->deliverables->count(),
                'score'               => $r['score'],
                'match_reasons'       => $r['match_reasons'],
                'deliverable_overlap' => $r['deliverable_overlap'],
            ])->values(),
        ]);
    }

    public function destroy(Proposal $proposal)
    {
        if ($proposal->project()->exists()) {
            return back()->with('error', 'Cannot delete a proposal that has an associated project.');
        }

        $ref = $proposal->ref;
        $proposal->delete();

        ActivityLog::record('Deleted proposal', 'proposals', null, $ref);

        return redirect()->route('proposals.index')
            ->with('success', "Proposal {$ref} deleted.");
    }
}
