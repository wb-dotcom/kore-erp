<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\ProposalLineItem;
use App\Models\ProposalRateSchedule;
use App\Services\GoogleDocsExportService;
use App\Services\ProposalFeeCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * AJAX controller for the proposal fee worksheet (Phase 1.5).
 *
 * All endpoints return JSON. The proposal show view drives a
 * dynamic fee worksheet table via fetch() calls.
 *
 * Routes (all under /proposals/{proposal}/line-items):
 *   GET    /                → index  — list all items grouped by phase
 *   POST   /                → store  — add a new line item (rate auto-resolved)
 *   PUT    /{item}          → update — update hours/role/override
 *   DELETE /{item}          → destroy — remove a line item
 *   GET    /resolve-rate    → resolveRate — look up the rate for a given role
 *   POST   /export-docs     → exportDocs  — push proposal to Google Docs
 */
class ProposalLineItemController extends Controller
{
    public function __construct(
        private readonly ProposalFeeCalculator $calculator
    ) {}

    // ── Line Item CRUD ─────────────────────────────────────────────────────────

    public function index(Proposal $proposal): JsonResponse
    {
        $proposal->load('lineItems');
        $byPhase = $this->calculator->summaryByPhase($proposal);

        return response()->json([
            'items'     => $proposal->lineItems,
            'by_phase'  => array_values($byPhase),
            'total_fee' => $proposal->total_fee,
        ]);
    }

    public function store(Request $request, Proposal $proposal): JsonResponse
    {
        if ($proposal->isApproved()) {
            return response()->json(['error' => 'Fee worksheet is locked — proposal is approved.'], 403);
        }

        $data = $request->validate([
            'phase_code'      => ['required', 'string', 'max:50'],
            'phase_label'     => ['nullable', 'string', 'max:100'],
            'deliverable'     => ['required', 'string', 'max:255'],
            'milestone'       => ['nullable', 'string', 'max:255'],
            'role_name'       => ['required', 'string', 'max:100'],
            'hours'           => ['required', 'numeric', 'min:0'],
            'rate'            => ['nullable', 'numeric', 'min:0'],
            'amount_override' => ['nullable', 'numeric', 'min:0'],
            'sort_order'      => ['nullable', 'integer'],
            'notes'           => ['nullable', 'string', 'max:500'],
        ]);

        $item = $this->calculator->addLineItem($proposal, $data);

        return response()->json([
            'item'      => $item->fresh(),
            'total_fee' => $proposal->fresh()->total_fee,
        ], 201);
    }

    public function update(Request $request, Proposal $proposal, ProposalLineItem $item): JsonResponse
    {
        $this->authoriseItem($proposal, $item);

        if ($proposal->isApproved()) {
            return response()->json(['error' => 'Fee worksheet is locked — proposal is approved.'], 403);
        }

        $data = $request->validate([
            'phase_code'      => ['sometimes', 'string', 'max:50'],
            'phase_label'     => ['nullable', 'string', 'max:100'],
            'deliverable'     => ['sometimes', 'string', 'max:255'],
            'milestone'       => ['nullable', 'string', 'max:255'],
            'role_name'       => ['sometimes', 'string', 'max:100'],
            'hours'           => ['sometimes', 'numeric', 'min:0'],
            'rate'            => ['nullable', 'numeric', 'min:0'],
            'amount_override' => ['nullable', 'numeric', 'min:0'],
            'sort_order'      => ['nullable', 'integer'],
            'notes'           => ['nullable', 'string', 'max:500'],
        ]);

        $item = $this->calculator->updateLineItem($item, $data);

        return response()->json([
            'item'      => $item,
            'total_fee' => $proposal->fresh()->total_fee,
        ]);
    }

    public function destroy(Proposal $proposal, ProposalLineItem $item): JsonResponse
    {
        $this->authoriseItem($proposal, $item);

        if ($proposal->isApproved()) {
            return response()->json(['error' => 'Fee worksheet is locked — proposal is approved.'], 403);
        }

        $this->calculator->removeLineItem($item);

        return response()->json([
            'deleted'   => true,
            'total_fee' => $proposal->fresh()->total_fee,
        ]);
    }

    // ── Rate Resolution ────────────────────────────────────────────────────────

    /**
     * Look up the billing rate for a role on this proposal.
     *
     * Used by the worksheet UI to pre-fill the rate field when a role is selected.
     *
     * GET /proposals/{proposal}/line-items/resolve-rate?role=Principal
     */
    public function resolveRate(Request $request, Proposal $proposal): JsonResponse
    {
        $roleName = $request->validate([
            'role' => ['required', 'string', 'max:100'],
        ])['role'];

        $rate = $this->calculator->resolveRate($proposal, $roleName);

        return response()->json(['role' => $roleName, 'rate' => $rate]);
    }

    // ── Google Docs Export ─────────────────────────────────────────────────────

    /**
     * Export this proposal to a new Google Doc and return the document URL.
     *
     * POST /proposals/{proposal}/line-items/export-docs
     */
    public function exportDocs(Proposal $proposal, GoogleDocsExportService $exporter): JsonResponse
    {
        try {
            $proposal->load(['lineItems', 'company', 'accountManager', 'status']);
            $docUrl = $exporter->export($proposal);

            return response()->json(['url' => $docUrl]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Google Docs export failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── Rate Schedule Overrides ────────────────────────────────────────────────

    /**
     * Add a per-proposal rate override for a specific role.
     *
     * POST /proposals/{proposal}/rate-schedules
     */
    public function storeRateSchedule(Request $request, Proposal $proposal): RedirectResponse
    {
        if ($proposal->isApproved()) {
            return back()->with('error', 'Rate overrides are locked — proposal is approved.');
        }

        $data = $request->validate([
            'scope'       => ['required', 'in:role,work_type,phase'],
            'scope_value' => ['required', 'string', 'max:100'],
            'hourly_rate' => ['required', 'numeric', 'min:0'],
        ]);

        $data['proposal_id'] = $proposal->id;

        ProposalRateSchedule::updateOrCreate(
            ['proposal_id' => $proposal->id, 'scope' => $data['scope'], 'scope_value' => $data['scope_value']],
            ['hourly_rate' => $data['hourly_rate']]
        );

        return back()->with('success', "Rate override for \"{$data['scope_value']}\" saved.");
    }

    /**
     * Remove a per-proposal rate override.
     *
     * DELETE /proposals/{proposal}/rate-schedules/{rateSchedule}
     */
    public function destroyRateSchedule(Proposal $proposal, ProposalRateSchedule $rateSchedule): RedirectResponse
    {
        if ($rateSchedule->proposal_id !== $proposal->id) {
            abort(403);
        }

        if ($proposal->isApproved()) {
            return back()->with('error', 'Rate overrides are locked — proposal is approved.');
        }

        $rateSchedule->delete();

        return back()->with('success', 'Rate override removed.');
    }

    // ── Private ────────────────────────────────────────────────────────────────

    /** Ensure the line item belongs to this proposal. */
    private function authoriseItem(Proposal $proposal, ProposalLineItem $item): void
    {
        if ($item->proposal_id !== $proposal->id) {
            abort(403);
        }
    }
}
