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

        $periods = $schedule->periods->map(function ($p) {
            $data = $p->toArray();
            if ($p->invoice_id) {
                $data['invoice_url']    = route('invoices.show', $p->invoice_id);
                $data['invoice_number'] = $p->invoice_number;
            }
            return $data;
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

        $fresh = $schedule->fresh('periods');
        $periodsWithLinks = $fresh->periods->map(function ($p) {
            $data = $p->toArray();
            if ($p->invoice_id) {
                $data['invoice_url'] = route('invoices.show', $p->invoice_id);
            }
            return $data;
        });

        return response()->json([
            'message'  => 'Billing schedule generated.',
            'schedule' => array_merge($fresh->toArray(), ['periods' => $periodsWithLinks]),
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

            // Build only the columns that are guaranteed to exist
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

            // Add nullable project link
            if ($proposal->project?->id) {
                $invoiceData['project_id'] = $proposal->project->id;
            }

            // Add new columns only if they exist in the schema (migration guard)
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
            $roleName = $user->title ?? $user->roles->first()?->name ?? 'Professional Staff';

            $rate = ProposalRateSchedule::where('proposal_id', $proposal->id)
                        ->where('role_name', $roleName)
                        ->value('rate')
                    ?? ScheduleOfFee::where('role_name', $roleName)->value('hourly_rate')
                    ?? $user->hourly_cost
                    ?? 0;

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

    private function buildFixedLineItems(Invoice $invoice, Proposal $proposal, BillingSchedulePeriod $period, string $billingType): void
    {
        $periodLabel = Carbon::parse($period->period_start)->format('M d')
                     . ' – '
                     . Carbon::parse($period->period_end)->format('M d, Y');

        // Per-deliverable: use notes to identify which deliverable this period is for
        if ($billingType === 'per_deliverable' && $period->notes) {
            // notes stores the deliverable name for per_deliverable periods
            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'description' => "Deliverable: {$period->notes}",
                'quantity'    => 1,
                'unit_price'  => $period->fees_amount,
                'line_total'  => $period->fees_amount,
                'sort_order'  => 0,
            ]);
        } else {
            $typeLabel = match ($billingType) {
                'fixed'           => 'Fixed Fee',
                'retainer'        => 'Retainer Fee',
                'per_deliverable' => 'Deliverable Fee',
                'hybrid'          => 'Professional Services',
                default           => 'Professional Services',
            };

            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'description' => "{$typeLabel} — {$periodLabel}",
                'quantity'    => 1,
                'unit_price'  => $period->fees_amount,
                'line_total'  => $period->fees_amount,
                'sort_order'  => 0,
            ]);
        }

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

        // Per-deliverable: one period per project deliverable using deliverable fees
        if ($schedule->billing_type === 'per_deliverable' && $proposal->project) {
            $deliverables = $proposal->project->deliverables()->orderBy('sort_order')->get();
            if ($deliverables->count() > 0) {
                $periods = [];
                foreach ($deliverables as $d) {
                    $fee       = (float) ($d->deliverable_fee ?? 0);
                    $dueDate   = $d->due_date ? Carbon::parse($d->due_date)->format('Y-m-d') : $end->format('Y-m-d');
                    $periods[] = [
                        'start'    => $start->format('Y-m-d'),
                        'end'      => $dueDate,
                        'fees'     => $fee,
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

        $feePerPeriod     = round($feesTotal / $count, 2);
        $expensePerPeriod = round($expensesTotal / $count, 2);
        $feeRemainder     = round($feesTotal - ($feePerPeriod * $count), 2);
        $expRemainder     = round($expensesTotal - ($expensePerPeriod * $count), 2);

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
