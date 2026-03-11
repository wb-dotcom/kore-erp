<?php

namespace App\Http\Controllers;

use App\Models\BillingSchedule;
use App\Models\BillingSchedulePeriod;
use App\Models\Proposal;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingScheduleController extends Controller
{
    /** GET /proposals/{proposal}/billing-schedule — return schedule + periods as JSON */
    public function show(Proposal $proposal): JsonResponse
    {
        $schedule = $proposal->billingSchedule()->with('periods')->first();

        if (! $schedule) {
            return response()->json(['schedule' => null, 'periods' => []]);
        }

        return response()->json([
            'schedule' => $schedule,
            'periods'  => $schedule->periods,
            'summary'  => [
                'total_invoiced' => $schedule->total_invoiced,
                'total_paid'     => $schedule->total_paid,
                'total_pending'  => $schedule->total_pending,
                'contract_value' => $proposal->contract_value ?? $proposal->total_fee ?? 0,
                'expenses_reserve' => $proposal->expenses_reserve ?? 0,
            ],
        ]);
    }

    /** POST /proposals/{proposal}/billing-schedule/generate — create/regenerate periods */
    public function generate(Request $request, Proposal $proposal): JsonResponse
    {
        $data = $request->validate([
            'billing_type'        => ['required', 'in:fixed,time_and_material,per_deliverable,retainer'],
            'billing_cycle'       => ['required', 'in:biweekly,monthly,quarterly,on_completion,custom'],
            'start_date'          => ['required', 'date'],
            'end_date'            => ['required', 'date', 'after:start_date'],
            'include_expenses'    => ['boolean'],
            'payment_terms_days'  => ['nullable', 'integer', 'min:0'],
        ]);

        // Upsert billing schedule record
        $schedule = BillingSchedule::updateOrCreate(
            ['proposal_id' => $proposal->id],
            [
                'billing_type'       => $data['billing_type'],
                'billing_cycle'      => $data['billing_cycle'],
                'start_date'         => $data['start_date'],
                'end_date'           => $data['end_date'],
                'include_expenses'   => $data['include_expenses'] ?? false,
                'payment_terms_days' => $data['payment_terms_days'] ?? $proposal->payment_terms_days ?? 30,
            ]
        );

        // Delete existing non-locked periods before regenerating
        $schedule->periods()->where('is_locked', false)->delete();

        // Generate periods
        $periods = $this->buildPeriods($schedule, $proposal);

        foreach ($periods as $index => $period) {
            BillingSchedulePeriod::create([
                'billing_schedule_id' => $schedule->id,
                'period_start'        => $period['start'],
                'period_end'          => $period['end'],
                'fees_amount'         => $period['fees'],
                'expenses_amount'     => $period['expenses'],
                'total_amount'        => $period['fees'] + $period['expenses'],
                'status'              => 'draft',
                'sort_order'          => $index + 1,
            ]);
        }

        return response()->json([
            'message'  => 'Billing schedule generated.',
            'schedule' => $schedule->fresh('periods'),
        ]);
    }

    /** PUT /proposals/{proposal}/billing-schedule/periods/{period} — update a period */
    public function updatePeriod(Request $request, Proposal $proposal, BillingSchedulePeriod $period): JsonResponse
    {
        if ($period->is_locked) {
            return response()->json(['message' => 'Period is locked and cannot be modified.'], 422);
        }

        $data = $request->validate([
            'fees_amount'     => ['nullable', 'numeric', 'min:0'],
            'expenses_amount' => ['nullable', 'numeric', 'min:0'],
            'status'          => ['nullable', 'in:draft,approved,invoiced,paid,overdue'],
            'invoice_number'  => ['nullable', 'string', 'max:50'],
            'notes'           => ['nullable', 'string'],
        ]);

        if (isset($data['fees_amount']) || isset($data['expenses_amount'])) {
            $fees     = $data['fees_amount'] ?? $period->fees_amount;
            $expenses = $data['expenses_amount'] ?? $period->expenses_amount;
            $data['total_amount'] = $fees + $expenses;
        }

        $period->update($data);

        // Lock the period if status moved to invoiced or paid
        if (in_array($data['status'] ?? '', ['invoiced', 'paid'])) {
            $period->update(['is_locked' => true]);
        }

        return response()->json(['period' => $period->fresh()]);
    }

    /** POST /proposals/{proposal}/billing-schedule/periods — add a manual period */
    public function storePeriod(Request $request, Proposal $proposal): JsonResponse
    {
        $schedule = $proposal->billingSchedule;

        if (! $schedule) {
            return response()->json(['message' => 'No billing schedule exists. Generate one first.'], 422);
        }

        $data = $request->validate([
            'period_start'    => ['required', 'date'],
            'period_end'      => ['required', 'date', 'after:period_start'],
            'fees_amount'     => ['required', 'numeric', 'min:0'],
            'expenses_amount' => ['nullable', 'numeric', 'min:0'],
            'notes'           => ['nullable', 'string'],
        ]);

        $fees     = $data['fees_amount'];
        $expenses = $data['expenses_amount'] ?? 0;

        $period = BillingSchedulePeriod::create([
            'billing_schedule_id' => $schedule->id,
            'period_start'        => $data['period_start'],
            'period_end'          => $data['period_end'],
            'fees_amount'         => $fees,
            'expenses_amount'     => $expenses,
            'total_amount'        => $fees + $expenses,
            'status'              => 'draft',
            'notes'               => $data['notes'] ?? null,
            'sort_order'          => $schedule->periods()->max('sort_order') + 1,
        ]);

        return response()->json(['period' => $period], 201);
    }

    /** DELETE /proposals/{proposal}/billing-schedule/periods/{period} */
    public function destroyPeriod(Proposal $proposal, BillingSchedulePeriod $period): JsonResponse
    {
        if ($period->is_locked) {
            return response()->json(['message' => 'Period is locked and cannot be deleted.'], 422);
        }

        $period->delete();

        return response()->json(['message' => 'Period deleted.']);
    }

    // ── Private Helpers ────────────────────────────────────────────────────────

    private function buildPeriods(BillingSchedule $schedule, Proposal $proposal): array
    {
        $start         = Carbon::parse($schedule->start_date);
        $end           = Carbon::parse($schedule->end_date);
        $contractValue = $proposal->contract_value ?? $proposal->total_fee ?? 0;
        $expensesTotal = ($schedule->include_expenses) ? ($proposal->expenses_reserve ?? 0) : 0;
        $feesTotal     = $contractValue - ($proposal->expenses_reserve ?? 0);

        $periods = [];

        match ($schedule->billing_cycle) {
            'biweekly'     => $periods = $this->splitByInterval($start, $end, '2 weeks'),
            'monthly'      => $periods = $this->splitByMonths($start, $end),
            'quarterly'    => $periods = $this->splitByQuarters($start, $end),
            'on_completion' => $periods = [['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d')]],
            default        => $periods = [['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d')]],
        };

        $count = count($periods);
        if ($count === 0) {
            return [];
        }

        $feePerPeriod     = round($feesTotal / $count, 2);
        $expensePerPeriod = $count > 0 ? round($expensesTotal / $count, 2) : 0;
        $feeRemainder     = round($feesTotal - ($feePerPeriod * $count), 2);
        $expRemainder     = round($expensesTotal - ($expensePerPeriod * $count), 2);

        foreach ($periods as $i => &$p) {
            // Last period absorbs rounding remainder
            $isLast      = ($i === $count - 1);
            $p['fees']     = $feePerPeriod + ($isLast ? $feeRemainder : 0);
            $p['expenses'] = $expensePerPeriod + ($isLast ? $expRemainder : 0);
        }

        return $periods;
    }

    private function splitByMonths(Carbon $start, Carbon $end): array
    {
        $periods = [];
        $cursor  = $start->copy()->startOfMonth();

        while ($cursor->lte($end)) {
            $periodStart = $cursor->copy();
            $periodEnd   = $cursor->copy()->endOfMonth();

            if ($periodEnd->gt($end)) {
                $periodEnd = $end->copy();
            }

            $periods[] = ['start' => $periodStart->format('Y-m-d'), 'end' => $periodEnd->format('Y-m-d')];
            $cursor->addMonth()->startOfMonth();
        }

        return $periods;
    }

    private function splitByQuarters(Carbon $start, Carbon $end): array
    {
        $periods = [];
        $cursor  = $start->copy();

        while ($cursor->lte($end)) {
            $qEnd = $cursor->copy()->endOfQuarter();
            if ($qEnd->gt($end)) {
                $qEnd = $end->copy();
            }
            $periods[] = ['start' => $cursor->format('Y-m-d'), 'end' => $qEnd->format('Y-m-d')];
            $cursor    = $qEnd->copy()->addDay()->startOfQuarter();
        }

        return $periods;
    }

    private function splitByInterval(Carbon $start, Carbon $end, string $interval): array
    {
        $periods = [];
        $cursor  = $start->copy();

        while ($cursor->lte($end)) {
            $periodEnd = $cursor->copy()->add($interval)->subDay();
            if ($periodEnd->gt($end)) {
                $periodEnd = $end->copy();
            }
            $periods[] = ['start' => $cursor->format('Y-m-d'), 'end' => $periodEnd->format('Y-m-d')];
            $cursor->add($interval);
        }

        return $periods;
    }
}
