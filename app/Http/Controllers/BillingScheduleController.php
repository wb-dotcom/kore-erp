<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BillingSchedule;
use App\Models\BillingSchedulePeriod;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Proposal;
use App\Models\ProposalRateSchedule;
use App\Models\ScheduleOfFee;
use App\Models\TimesheetEntry;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingScheduleController extends Controller
{
    /** GET /proposals/{proposal}/billing-schedule */
    public function show(Proposal $proposal): JsonResponse
    {
        $schedule = $proposal->billingSchedule()->with('periods')->first();

        if (! $schedule) {
            return response()->json(['schedule' => null, 'periods' => []]);
        }

        $periods = $schedule->periods->values()->map(function ($p, $i) use ($schedule, $proposal) {
            return $this->mapPeriod($p, $schedule, $proposal, $i, $schedule->periods->count());
        });

        return response()->json([
            'schedule' => $schedule,
            'periods'  => $periods,
            'summary'  => [
                'total_invoiced'   => $schedule->total_invoiced,
                'total_paid'       => $schedule->total_paid,
                'total_pending'    => $schedule->total_pending,
                'contract_value'   => $proposal->contract_value ?? $proposal->total_fee ?? 0,
                'expenses_reserve' => $proposal->expenses_reserve ?? 0,
            ],
        ]);
    }

    /** POST /proposals/{proposal}/billing-schedule/generate */
    public function generate(Request $request, Proposal $proposal): JsonResponse
    {
        $data = $request->validate([
            'billing_type'        => ['required', 'in:fixed,time_and_material,per_deliverable,retainer,hybrid'],
            'billing_cycle'       => ['required', 'in:biweekly,monthly,quarterly,on_completion,custom'],
            'start_date'          => ['required', 'date'],
            'end_date'            => ['required', 'date', 'after:start_date'],
            'include_expenses'    => ['boolean'],
            'payment_terms_days'  => ['nullable', 'integer', 'min:0'],
        ]);

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

        $schedule->periods()->where('is_locked', false)->delete();

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
                'notes'               => $period['notes'] ?? null,
            ]);
        }

        // Auto-compute actual fees from recorded work for data-driven billing types.
        // Fixed and retainer use the pre-split contract value; others start at $0
        // and must be populated from real timesheet/deliverable data.
        $isDataDriven = in_array($schedule->billing_type, ['time_and_material', 'hybrid', 'per_deliverable']);
        if ($isDataDriven) {
            foreach ($schedule->fresh('periods')->periods as $p) {
                $fees = 0.0;
                if (in_array($schedule->billing_type, ['time_and_material', 'hybrid'])) {
                    $fees += $this->sumTimesheetFees($proposal, $p);
                }
                if (in_array($schedule->billing_type, ['per_deliverable', 'hybrid'])) {
                    $fees += $this->sumDeliverableFees($proposal, $p);
                }
                $p->update([
                    'fees_amount'  => round($fees, 2),
                    'total_amount' => round($fees + (float) $p->expenses_amount, 2),
                ]);
            }
        }

        $fresh   = $schedule->fresh('periods');
        $total   = $fresh->periods->count();
        $mapped  = $fresh->periods->values()->map(function ($p, $i) use ($schedule, $proposal, $total) {
            return $this->mapPeriod($p, $schedule, $proposal, $i, $total);
        });

        return response()->json([
            'message'  => 'Billing schedule generated.',
            'schedule' => array_merge($fresh->toArray(), ['periods' => $mapped]),
        ]);
    }

    /**
     * POST /proposals/{proposal}/billing-schedule/compute-all
     * Re-computes fees for all unlocked data-driven periods from actual work data.
     */
    public function computeAllPeriods(Proposal $proposal): JsonResponse
    {
        $schedule = $proposal->billingSchedule()->with('periods')->first();
        if (! $schedule) {
            return response()->json(['message' => 'No billing schedule found.'], 422);
        }

        if (! in_array($schedule->billing_type, ['time_and_material', 'hybrid', 'per_deliverable'])) {
            return response()->json(['message' => 'Compute-all only applies to T&M, hybrid, or per-deliverable billing.'], 422);
        }

        $updated = 0;
        foreach ($schedule->periods()->where('is_locked', false)->get() as $period) {
            $fees = 0.0;
            if (in_array($schedule->billing_type, ['time_and_material', 'hybrid'])) {
                $fees += $this->sumTimesheetFees($proposal, $period);
            }
            if (in_array($schedule->billing_type, ['per_deliverable', 'hybrid'])) {
                $fees += $this->sumDeliverableFees($proposal, $period);
            }
            $period->update([
                'fees_amount'  => round($fees, 2),
                'total_amount' => round($fees + (float) $period->expenses_amount, 2),
            ]);
            $updated++;
        }

        $fresh  = $schedule->fresh('periods');
        $total  = $fresh->periods->count();
        $mapped = $fresh->periods->values()->map(function ($p, $i) use ($schedule, $proposal, $total) {
            return $this->mapPeriod($p, $schedule, $proposal, $i, $total);
        });

        return response()->json([
            'message' => "Synced fees for {$updated} period(s) from actual work data.",
            'periods' => $mapped,
        ]);
    }

    /**
     * POST /proposals/{proposal}/billing-schedule/periods/{period}/generate-invoice
     * Creates a real invoice from a billing period, linked to this proposal.
     */
    public function generateInvoice(Request $request, Proposal $proposal, BillingSchedulePeriod $period): JsonResponse
    {
        try {
            if ($period->is_locked && $period->invoice_id) {
                return response()->json([
                    'message'     => 'Invoice already exists for this period.',
                    'invoice_url' => route('invoices.show', $period->invoice_id),
                ], 422);
            }

            $schedule    = $period->billingSchedule;
            $billingType = $proposal->billing_type ?? $schedule->billing_type ?? 'fixed';

            $invoiceNumber = Invoice::nextNumber();
            $invoiceDate   = now()->toDateString();
            $dueDate       = now()->addDays($schedule->payment_terms_days ?? 30)->toDateString();

            $invoiceType = match ($billingType) {
                'time_and_material' => 'tm_auto',
                'retainer'          => 'retainer',
                default             => 'fixed_auto',
            };

            $invoiceData = [
                'invoice_number' => $invoiceNumber,
                'company_id'     => $proposal->company_id,
                'invoice_date'   => $invoiceDate,
                'due_date'       => $dueDate,
                'tax_rate'       => 0,
                'status'         => 'draft',
                'notes'          => 'Billing period: '
                                  . Carbon::parse($period->period_start)->format('M d, Y')
                                  . ' – '
                                  . Carbon::parse($period->period_end)->format('M d, Y'),
                'created_by'     => auth()->id(),
            ];

            if ($proposal->project?->id) {
                $invoiceData['project_id'] = $proposal->project->id;
            }

            $invoiceColumns = \Schema::getColumnListing('invoices');
            if (in_array('proposal_id', $invoiceColumns)) {
                $invoiceData['proposal_id'] = $proposal->id;
            }
            if (in_array('billing_schedule_period_id', $invoiceColumns)) {
                $invoiceData['billing_schedule_period_id'] = $period->id;
            }
            if (in_array('invoice_type', $invoiceColumns)) {
                $invoiceData['invoice_type'] = $invoiceType;
            }

            $invoice = Invoice::create($invoiceData);

            if ($billingType === 'time_and_material') {
                $this->buildTimesheetLineItems($invoice, $proposal, $period);
            } elseif ($billingType === 'hybrid') {
                $this->buildHybridLineItems($invoice, $proposal, $period);
            } elseif ($billingType === 'per_deliverable') {
                $this->buildDeliverableLineItems($invoice, $proposal, $period);
            } else {
                $this->buildFixedLineItems($invoice, $proposal, $period, $billingType);
            }

            $invoice->recalculate();

            $period->update([
                'status'         => 'invoiced',
                'invoice_id'     => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'is_locked'      => true,
            ]);

            ActivityLog::record(
                'Generated invoice from billing period',
                'invoices',
                $invoice->id,
                $invoice->invoice_number
            );

            return response()->json([
                'success'        => true,
                'invoice_id'     => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'invoice_url'    => route('invoices.show', $invoice),
                'total'          => (float) $invoice->total,
            ]);

        } catch (\Throwable $e) {
            \Log::error('generateInvoice failed: ' . $e->getMessage(), [
                'proposal_id' => $proposal->id,
                'period_id'   => $period->id,
                'trace'       => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate invoice: ' . $e->getMessage(),
            ], 500);
        }
    }

    /** PUT /proposals/{proposal}/billing-schedule/periods/{period} */
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
            $fees              = $data['fees_amount'] ?? $period->fees_amount;
            $expenses          = $data['expenses_amount'] ?? $period->expenses_amount;
            $data['total_amount'] = $fees + $expenses;
        }

        $period->update($data);

        if (in_array($data['status'] ?? '', ['invoiced', 'paid'])) {
            $period->update(['is_locked' => true]);
        }

        return response()->json(['period' => $period->fresh()]);
    }

    /** POST /proposals/{proposal}/billing-schedule/periods */
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

    // ── Period Compute & Breakdown ─────────────────────────────────────────────

    /**
     * GET /proposals/{proposal}/billing-schedule/periods/{period}/breakdown
     * Returns the hours/deliverable data available for computing this period's fees.
     */
    public function periodBreakdown(Proposal $proposal, BillingSchedulePeriod $period): JsonResponse
    {
        $schedule    = $period->billingSchedule;
        $billingType = $schedule->billing_type;

        $result = [
            'period_id'    => $period->id,
            'billing_type' => $billingType,
            'period_start' => Carbon::parse($period->period_start)->toDateString(),
            'period_end'   => Carbon::parse($period->period_end)->toDateString(),
            'current_fees' => (float) $period->fees_amount,
        ];

        if (in_array($billingType, ['time_and_material', 'hybrid'])) {
            $result['timesheet'] = $this->getTimesheetBreakdown($proposal, $period);
        }

        if (in_array($billingType, ['per_deliverable', 'hybrid'])) {
            $result['deliverables'] = $this->getDeliverableBreakdown($proposal, $period);
        }

        // Computed total preview
        $result['computed_fees'] = ($result['timesheet']['total_fees'] ?? 0)
                                 + ($result['deliverables']['total_fees'] ?? 0);

        return response()->json($result);
    }

    /**
     * POST /proposals/{proposal}/billing-schedule/periods/{period}/compute-fees
     * Calculates fees from actual timesheet hours and/or completed deliverables.
     */
    public function computePeriodFees(Request $request, Proposal $proposal, BillingSchedulePeriod $period): JsonResponse
    {
        if ($period->is_locked) {
            return response()->json(['message' => 'Period is locked and cannot be modified.'], 422);
        }

        $schedule    = $period->billingSchedule;
        $billingType = $schedule->billing_type;

        $fees = 0.0;

        if (in_array($billingType, ['time_and_material', 'hybrid'])) {
            $fees += $this->sumTimesheetFees($proposal, $period);
        }

        if (in_array($billingType, ['per_deliverable', 'hybrid'])) {
            $fees += $this->sumDeliverableFees($proposal, $period);
        }

        // For hybrid the user can also supply a fixed component on top of T&M
        if ($billingType === 'hybrid') {
            $fees += (float) ($request->input('fixed_component', 0));
        }

        $fees     = round($fees, 2);
        $expenses = (float) $period->expenses_amount;

        $period->update([
            'fees_amount'  => $fees,
            'total_amount' => $fees + $expenses,
        ]);

        $context = $this->buildPeriodContext($period->fresh(), $schedule, $proposal);

        return response()->json([
            'message' => 'Fees computed successfully.',
            'period'  => array_merge($period->fresh()->toArray(), ['context' => $context]),
        ]);
    }

    // ── Private: Period Response Builder ──────────────────────────────────────

    /**
     * Maps a BillingSchedulePeriod to a response array that includes
     * billing-type-specific context (hours logged, deliverable status, etc.)
     * so the frontend can render intelligent period rows.
     */
    private function mapPeriod(
        BillingSchedulePeriod $period,
        BillingSchedule $schedule,
        Proposal $proposal,
        int $index,
        int $total
    ): array {
        $data = $period->toArray();

        if ($period->invoice_id) {
            $data['invoice_url']    = route('invoices.show', $period->invoice_id);
            $data['invoice_number'] = $period->invoice_number;
        }

        $data['context'] = $this->buildPeriodContext($period, $schedule, $proposal, $index, $total);

        return $data;
    }

    /**
     * Builds billing-type-specific context for a period.
     * This drives which input mechanism is shown in the billing schedule UI.
     *
     *  - fixed/retainer  → period_index, period_count (shows "Period N of N")
     *  - time_and_material/hybrid → hours_logged, hours_fee (shows "X hrs · $Y")
     *  - per_deliverable → deliverable_name, deliverable_status (shows deliverable badge)
     *  - hybrid          → hours_logged + hours_fee + note about fixed component
     */
    private function buildPeriodContext(
        BillingSchedulePeriod $period,
        BillingSchedule $schedule,
        Proposal $proposal,
        int $index = 0,
        int $total = 1
    ): array {
        $ctx = [
            'period_index'       => $index + 1,
            'period_count'       => $total,
            'hours_logged'       => null,
            'hours_fee'          => null,
            'deliverable_name'   => null,
            'deliverable_status' => null,
        ];

        $billingType = $schedule->billing_type;

        // T&M and hybrid: query actual billable hours for this date range
        if (in_array($billingType, ['time_and_material', 'hybrid'])) {
            $project = $proposal->project;
            if ($project) {
                $start = Carbon::parse($period->period_start)->toDateString();
                $end   = Carbon::parse($period->period_end)->toDateString();

                $entries = TimesheetEntry::with(['timesheet.user'])
                    ->where('project_id', $project->id)
                    ->where('entry_type', 'billable')
                    ->whereBetween('entry_date', [$start, $end])
                    ->get();

                $totalHours = 0.0;
                $totalFees  = 0.0;
                foreach ($entries as $entry) {
                    $user       = $entry->timesheet->user;
                    $rate       = ProposalRateSchedule::resolveRateForUser($user, $proposal);
                    $totalHours += $entry->hours;
                    $totalFees  += $entry->hours * $rate;
                }

                $ctx['hours_logged'] = round($totalHours, 2);
                $ctx['hours_fee']    = round($totalFees, 2);
            } else {
                $ctx['hours_logged'] = 0;
                $ctx['hours_fee']    = 0;
            }
        }

        // Per-deliverable: resolve deliverable name (stored in notes) and its billing status
        if ($billingType === 'per_deliverable' && $period->notes) {
            $project = $proposal->project;
            $ctx['deliverable_name'] = $period->notes;
            if ($project) {
                $d = $project->deliverables()->where('name', $period->notes)->first();
                $ctx['deliverable_status'] = $d?->billing_status ?? 'pending';
                $ctx['deliverable_fee']    = (float) ($d?->deliverable_fee ?? 0);
            } else {
                $ctx['deliverable_status'] = 'pending';
                $ctx['deliverable_fee']    = 0;
            }
        }

        return $ctx;
    }

    // ── Private: Fee Computation Helpers ──────────────────────────────────────

    private function sumTimesheetFees(Proposal $proposal, BillingSchedulePeriod $period): float
    {
        $project = $proposal->project;
        if (! $project) return 0.0;

        $start   = Carbon::parse($period->period_start)->toDateString();
        $end     = Carbon::parse($period->period_end)->toDateString();

        $entries = TimesheetEntry::with(['timesheet.user'])
            ->where('project_id', $project->id)
            ->where('entry_type', 'billable')
            ->whereBetween('entry_date', [$start, $end])
            ->get();

        $total = 0.0;
        foreach ($entries as $entry) {
            $user  = $entry->timesheet->user;
            $rate  = ProposalRateSchedule::resolveRateForUser($user, $proposal);
            $total += $entry->hours * $rate;
        }

        return round($total, 2);
    }

    private function sumDeliverableFees(Proposal $proposal, BillingSchedulePeriod $period): float
    {
        $project = $proposal->project;
        if (! $project) return 0.0;

        // Per-deliverable periods store the deliverable name in notes
        $name = $period->notes;
        if ($name) {
            $d = $project->deliverables()->where('name', $name)->first();
            if ($d && $d->billing_status === 'ready_to_bill') {
                return (float) ($d->deliverable_fee ?? 0);
            }
            return 0.0;
        }

        // Hybrid: sum all ready-to-bill deliverables
        return $project->deliverables()
            ->where('billing_status', 'ready_to_bill')
            ->sum('deliverable_fee') ?? 0.0;
    }

    private function getTimesheetBreakdown(Proposal $proposal, BillingSchedulePeriod $period): array
    {
        $project = $proposal->project;
        if (! $project) {
            return ['rows' => [], 'total_hours' => 0, 'total_fees' => 0, 'has_project' => false];
        }

        $start   = Carbon::parse($period->period_start)->toDateString();
        $end     = Carbon::parse($period->period_end)->toDateString();

        $entries = TimesheetEntry::with(['timesheet.user'])
            ->where('project_id', $project->id)
            ->where('entry_type', 'billable')
            ->whereBetween('entry_date', [$start, $end])
            ->get();

        $grouped    = [];
        $totalFees  = 0.0;
        $totalHours = 0.0;

        foreach ($entries as $entry) {
            $user = $entry->timesheet->user;
            $rate = ProposalRateSchedule::resolveRateForUser($user, $proposal);
            $key  = $user->id . '_' . ($user->role?->name ?? '');

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'user_name' => $user->name,
                    'role'      => $user->role?->name ?? 'Staff',
                    'hours'     => 0.0,
                    'rate'      => $rate,
                    'subtotal'  => 0.0,
                ];
            }
            $grouped[$key]['hours']   += $entry->hours;
            $grouped[$key]['subtotal'] = round($grouped[$key]['hours'] * $rate, 2);
            $totalHours += $entry->hours;
            $totalFees  += $entry->hours * $rate;
        }

        return [
            'has_project' => true,
            'rows'        => array_values($grouped),
            'total_hours' => round($totalHours, 2),
            'total_fees'  => round($totalFees, 2),
        ];
    }

    private function getDeliverableBreakdown(Proposal $proposal, BillingSchedulePeriod $period): array
    {
        $project = $proposal->project;
        if (! $project) {
            return ['rows' => [], 'total_fees' => 0, 'has_project' => false];
        }

        $name  = $period->notes;
        $query = $project->deliverables();
        if ($name) {
            $query->where('name', $name);
        }

        $deliverables = $query->get();
        $rows         = [];
        $totalFees    = 0.0;

        foreach ($deliverables as $d) {
            $ready   = $d->billing_status === 'ready_to_bill';
            $fee     = (float) ($d->deliverable_fee ?? 0);
            $rows[]  = [
                'name'           => $d->name,
                'billing_status' => $d->billing_status ?? 'pending',
                'fee'            => $fee,
                'ready'          => $ready,
            ];
            if ($ready) $totalFees += $fee;
        }

        return [
            'has_project' => true,
            'rows'        => $rows,
            'total_fees'  => round($totalFees, 2),
        ];
    }

    // ── Line Item Builders ─────────────────────────────────────────────────────

    private function buildTimesheetLineItems(Invoice $invoice, Proposal $proposal, BillingSchedulePeriod $period): void
    {
        $project = $proposal->project;

        if (! $project) {
            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'description' => 'Time & Material Services — no linked project yet',
                'quantity'    => 0,
                'unit_price'  => 0,
                'line_total'  => 0,
                'sort_order'  => 0,
            ]);
            return;
        }

        $periodStart = Carbon::parse($period->period_start)->toDateString();
        $periodEnd   = Carbon::parse($period->period_end)->toDateString();

        $entries = TimesheetEntry::with(['timesheet.user'])
            ->where('project_id', $project->id)
            ->where('entry_type', 'billable')
            ->whereBetween('entry_date', [$periodStart, $periodEnd])
            ->get();

        if ($entries->isEmpty()) {
            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'description' => "No billable hours recorded for {$periodStart} – {$periodEnd}",
                'quantity'    => 0,
                'unit_price'  => 0,
                'line_total'  => 0,
                'sort_order'  => 0,
            ]);
            return;
        }

        $grouped = [];
        foreach ($entries as $entry) {
            $user     = $entry->timesheet->user;
            $rate     = ProposalRateSchedule::resolveRateForUser($user, $proposal);
            $roleName = $user->role?->name ?? 'Professional Staff';

            $key = $user->id . '_' . $roleName;
            if (! isset($grouped[$key])) {
                $grouped[$key] = ['user' => $user, 'role' => $roleName, 'hours' => 0, 'rate' => $rate, 'entry_ids' => []];
            }
            $grouped[$key]['hours']     += $entry->hours;
            $grouped[$key]['entry_ids'][] = $entry->id;
        }

        foreach (array_values($grouped) as $i => $group) {
            $lineTotal = round($group['hours'] * $group['rate'], 2);
            InvoiceItem::create([
                'invoice_id'          => $invoice->id,
                'user_id'             => $group['user']->id,
                'role_name'           => $group['role'],
                'hours'               => $group['hours'],
                'rate'                => $group['rate'],
                'timesheet_entry_ids' => $group['entry_ids'],
                'description'         => "{$group['role']} — {$group['user']->name}: {$group['hours']} hrs @ \${$group['rate']}/hr",
                'quantity'            => $group['hours'],
                'unit_price'          => $group['rate'],
                'line_total'          => $lineTotal,
                'sort_order'          => $i,
            ]);
        }
    }

    private function buildDeliverableLineItems(Invoice $invoice, Proposal $proposal, BillingSchedulePeriod $period): void
    {
        $project = $proposal->project;

        if (! $project || ! $period->notes) {
            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'description' => $period->notes ? "Deliverable: {$period->notes}" : 'Deliverable Services',
                'quantity'    => 1,
                'unit_price'  => $period->fees_amount,
                'line_total'  => $period->fees_amount,
                'sort_order'  => 0,
            ]);
            return;
        }

        $d = $project->deliverables()->where('name', $period->notes)->first();
        $fee = $d?->billing_status === 'ready_to_bill' ? (float) ($d->deliverable_fee ?? 0) : 0.0;

        InvoiceItem::create([
            'invoice_id'  => $invoice->id,
            'description' => "Deliverable: {$period->notes}",
            'quantity'    => 1,
            'unit_price'  => $fee,
            'line_total'  => $fee,
            'sort_order'  => 0,
        ]);

        if ($period->expenses_amount > 0) {
            $periodLabel = Carbon::parse($period->period_start)->format('M d')
                         . ' – ' . Carbon::parse($period->period_end)->format('M d, Y');
            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'description' => "Reimbursable Expenses — {$periodLabel}",
                'quantity'    => 1,
                'unit_price'  => $period->expenses_amount,
                'line_total'  => $period->expenses_amount,
                'sort_order'  => 1,
            ]);
        }
    }

    private function buildHybridLineItems(Invoice $invoice, Proposal $proposal, BillingSchedulePeriod $period): void
    {
        // T&M component
        $this->buildTimesheetLineItems($invoice, $proposal, $period);

        // Deliverable component (ready-to-bill items not yet counted)
        $project = $proposal->project;
        if ($project) {
            $readyDeliverables = $project->deliverables()
                ->where('billing_status', 'ready_to_bill')
                ->get();

            foreach ($readyDeliverables as $i => $d) {
                $fee = (float) ($d->deliverable_fee ?? 0);
                if ($fee > 0) {
                    InvoiceItem::create([
                        'invoice_id'  => $invoice->id,
                        'description' => "Deliverable: {$d->name}",
                        'quantity'    => 1,
                        'unit_price'  => $fee,
                        'line_total'  => $fee,
                        'sort_order'  => 100 + $i,
                    ]);
                }
            }
        }

        if ($period->expenses_amount > 0) {
            $periodLabel = Carbon::parse($period->period_start)->format('M d')
                         . ' – ' . Carbon::parse($period->period_end)->format('M d, Y');
            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'description' => "Reimbursable Expenses — {$periodLabel}",
                'quantity'    => 1,
                'unit_price'  => $period->expenses_amount,
                'line_total'  => $period->expenses_amount,
                'sort_order'  => 200,
            ]);
        }
    }

    private function buildFixedLineItems(Invoice $invoice, Proposal $proposal, BillingSchedulePeriod $period, string $billingType): void
    {
        $periodLabel = Carbon::parse($period->period_start)->format('M d')
                     . ' – '
                     . Carbon::parse($period->period_end)->format('M d, Y');

        $typeLabel = match ($billingType) {
            'fixed'   => 'Fixed Fee',
            'retainer' => 'Retainer Fee',
            default    => 'Professional Services',
        };

        InvoiceItem::create([
            'invoice_id'  => $invoice->id,
            'description' => "{$typeLabel} — {$periodLabel}",
            'quantity'    => 1,
            'unit_price'  => $period->fees_amount,
            'line_total'  => $period->fees_amount,
            'sort_order'  => 0,
        ]);

        if ($period->expenses_amount > 0) {
            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'description' => "Reimbursable Expenses — {$periodLabel}",
                'quantity'    => 1,
                'unit_price'  => $period->expenses_amount,
                'line_total'  => $period->expenses_amount,
                'sort_order'  => 1,
            ]);
        }
    }

    // ── Period Splitters ───────────────────────────────────────────────────────

    private function buildPeriods(BillingSchedule $schedule, Proposal $proposal): array
    {
        $start         = Carbon::parse($schedule->start_date);
        $end           = Carbon::parse($schedule->end_date);
        $contractValue = $proposal->contract_value ?? $proposal->total_fee ?? 0;
        $expensesTotal = $schedule->include_expenses ? ($proposal->expenses_reserve ?? 0) : 0;
        $feesTotal     = $contractValue - ($proposal->expenses_reserve ?? 0);

        // Per-deliverable: one period per project deliverable, fees start at $0 until deliverable is complete
        if ($schedule->billing_type === 'per_deliverable' && $proposal->project) {
            $deliverables = $proposal->project->deliverables()->orderBy('sort_order')->get();
            if ($deliverables->count() > 0) {
                $periods = [];
                foreach ($deliverables as $d) {
                    $dueDate   = $d->due_date ? Carbon::parse($d->due_date)->format('Y-m-d') : $end->format('Y-m-d');
                    $periods[] = [
                        'start'    => $start->format('Y-m-d'),
                        'end'      => $dueDate,
                        'fees'     => 0,
                        'expenses' => 0,
                        'notes'    => $d->name,
                    ];
                }
                return $periods;
            }
        }

        $periods = match ($schedule->billing_cycle) {
            'biweekly'      => $this->splitByInterval($start, $end, '2 weeks'),
            'monthly'       => $this->splitByMonths($start, $end),
            'quarterly'     => $this->splitByQuarters($start, $end),
            'on_completion' => [['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d')]],
            default         => [['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d')]],
        };

        $count = count($periods);
        if ($count === 0) return [];

        // Only fixed and retainer auto-divide contract value across periods.
        // T&M, hybrid, and per_deliverable start at $0 — fees are computed
        // from actual timesheet entries / deliverable statuses after period creation.
        $isAutoCalculated = in_array($schedule->billing_type, ['fixed', 'retainer']);

        $feePerPeriod     = $isAutoCalculated ? round($feesTotal / $count, 2) : 0;
        $expensePerPeriod = $isAutoCalculated ? round($expensesTotal / $count, 2) : 0;
        $feeRemainder     = $isAutoCalculated ? round($feesTotal - ($feePerPeriod * $count), 2) : 0;
        $expRemainder     = $isAutoCalculated ? round($expensesTotal - ($expensePerPeriod * $count), 2) : 0;

        foreach ($periods as $i => &$p) {
            $isLast        = ($i === $count - 1);
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
            $periodEnd = $cursor->copy()->endOfMonth();
            if ($periodEnd->gt($end)) $periodEnd = $end->copy();
            $periods[] = ['start' => $cursor->format('Y-m-d'), 'end' => $periodEnd->format('Y-m-d')];
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
            if ($qEnd->gt($end)) $qEnd = $end->copy();
            $periods[] = ['start' => $cursor->format('Y-m-d'), 'end' => $qEnd->format('Y-m-d')];
            $cursor    = $qEnd->copy()->addDay();
        }
        return $periods;
    }

    private function splitByInterval(Carbon $start, Carbon $end, string $interval): array
    {
        $periods = [];
        $cursor  = $start->copy();
        while ($cursor->lte($end)) {
            $periodEnd = $cursor->copy()->add($interval)->subDay();
            if ($periodEnd->gt($end)) $periodEnd = $end->copy();
            $periods[] = ['start' => $cursor->format('Y-m-d'), 'end' => $periodEnd->format('Y-m-d')];
            $cursor->add($interval);
        }
        return $periods;
    }
}
