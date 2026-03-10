<?php

namespace App\Services;

use App\Models\Proposal;
use App\Models\ProposalLineItem;
use App\Models\ScheduleOfFee;

/**
 * Builds and recalculates the fee worksheet for a proposal.
 *
 * Rate resolution hierarchy (highest priority first):
 *   1. proposal_rate_schedules with scope='role' for this role_name
 *   2. schedule_of_fees for this role_name (current effective rate)
 *
 * After any line item change, call recalculateTotals() to update
 * proposals.total_fee (the denormalised aggregate).
 */
class ProposalFeeCalculator
{
    // ── Line Item Creation ─────────────────────────────────────────────────────

    /**
     * Add a line item to the proposal, resolving the billing rate automatically.
     *
     * @param  Proposal  $proposal
     * @param  array{
     *   phase_code: string,
     *   phase_label?: string,
     *   deliverable: string,
     *   milestone?: string,
     *   role_name: string,
     *   hours: float,
     *   rate?: float,       // explicit rate, skips resolution
     *   amount_override?: float,
     *   sort_order?: int,
     *   notes?: string
     * }  $data
     */
    public function addLineItem(Proposal $proposal, array $data): ProposalLineItem
    {
        $rate = $data['rate'] ?? $this->resolveRate($proposal, $data['role_name']);

        $hours  = (float) ($data['hours'] ?? 0);
        $amount = round($hours * $rate, 2);

        $item = ProposalLineItem::create([
            'proposal_id'     => $proposal->id,
            'phase_code'      => $data['phase_code'],
            'phase_label'     => $data['phase_label'] ?? null,
            'deliverable'     => $data['deliverable'],
            'milestone'       => $data['milestone'] ?? null,
            'role_name'       => $data['role_name'],
            'hours'           => $hours,
            'rate'            => $rate,
            'amount'          => $amount,
            'amount_override' => $data['amount_override'] ?? null,
            'sort_order'      => $data['sort_order'] ?? 0,
            'notes'           => $data['notes'] ?? null,
        ]);

        $this->recalculateTotals($proposal);

        return $item;
    }

    /**
     * Update hours/rate/override on an existing line item and recompute totals.
     */
    public function updateLineItem(ProposalLineItem $item, array $data): ProposalLineItem
    {
        $proposal = $item->proposal;

        if (isset($data['role_name']) && $data['role_name'] !== $item->role_name) {
            // Role changed — re-resolve rate unless explicitly provided
            $data['rate'] = $data['rate'] ?? $this->resolveRate($proposal, $data['role_name']);
        }

        $hours = (float) ($data['hours'] ?? $item->hours);
        $rate  = (float) ($data['rate'] ?? $item->rate);

        $item->update(array_merge($data, [
            'hours'  => $hours,
            'rate'   => $rate,
            'amount' => round($hours * $rate, 2),
        ]));

        $this->recalculateTotals($proposal);

        return $item->fresh();
    }

    /**
     * Remove a line item and recompute proposal totals.
     */
    public function removeLineItem(ProposalLineItem $item): void
    {
        $proposal = $item->proposal;
        $item->delete();
        $this->recalculateTotals($proposal);
    }

    // ── Aggregation ────────────────────────────────────────────────────────────

    /**
     * Recompute proposals.total_fee from all line items.
     * Call after any line item insert/update/delete.
     */
    public function recalculateTotals(Proposal $proposal): void
    {
        $total = $proposal->lineItems()
            ->get()
            ->sum(fn ($item) => $item->effective_amount);

        $proposal->update(['total_fee' => round($total, 2)]);
    }

    /**
     * Return a summary keyed by phase_code.
     *
     * Returns: [ 'SD' => ['label' => 'Schematic Design', 'hours' => 120, 'amount' => 18000], ... ]
     */
    public function summaryByPhase(Proposal $proposal): array
    {
        $summary = [];

        foreach ($proposal->lineItems()->orderBy('sort_order')->get() as $item) {
            $code = $item->phase_code;

            if (! isset($summary[$code])) {
                $summary[$code] = [
                    'code'   => $code,
                    'label'  => $item->phase_label ?? $code,
                    'hours'  => 0.0,
                    'amount' => 0.0,
                    'items'  => [],
                ];
            }

            $summary[$code]['hours']  += $item->hours;
            $summary[$code]['amount'] += $item->effective_amount;
            $summary[$code]['items'][] = $item;
        }

        return $summary;
    }

    /**
     * Return a summary keyed by role_name.
     *
     * Returns: [ 'Principal' => ['hours' => 40, 'rate' => 250, 'amount' => 10000], ... ]
     */
    public function summaryByRole(Proposal $proposal): array
    {
        $summary = [];

        foreach ($proposal->lineItems()->get() as $item) {
            $role = $item->role_name;

            if (! isset($summary[$role])) {
                $summary[$role] = [
                    'role'   => $role,
                    'rate'   => $item->rate,
                    'hours'  => 0.0,
                    'amount' => 0.0,
                ];
            }

            $summary[$role]['hours']  += $item->hours;
            $summary[$role]['amount'] += $item->effective_amount;
        }

        return $summary;
    }

    // ── Rate Resolution ────────────────────────────────────────────────────────

    /**
     * Resolve the billing rate for a role on this proposal.
     *
     * Priority:
     *   1. proposal_rate_schedules (scope='role') for this proposal + role
     *   2. schedule_of_fees current rate for this role
     *   3. 0.00 (no rate configured — PM must enter manually)
     */
    public function resolveRate(Proposal $proposal, string $roleName): float
    {
        // 1. Check negotiated per-proposal rate
        $negotiated = $proposal->rateSchedules()
            ->where('scope', 'role')
            ->where('scope_value', $roleName)
            ->value('hourly_rate');

        if ($negotiated !== null) {
            return (float) $negotiated;
        }

        // 2. Global schedule of fees
        return ScheduleOfFee::rateForRole($roleName);
    }
}
