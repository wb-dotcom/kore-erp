<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Project;
use App\Models\Proposal;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    // ── AR/AP Dashboard ────────────────────────────────────────────────────────

    public function dashboard()
    {
        // Auto-mark overdue
        Invoice::where('status', 'sent')
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        $now = now();

        // KPI totals
        $kpis = [
            'total_invoiced'  => Invoice::whereNotIn('status', ['void', 'draft'])->sum('total'),
            'total_paid'      => Invoice::where('status', 'paid')->sum('total'),
            'total_outstanding' => Invoice::whereIn('status', ['sent', 'partial', 'overdue'])->sum('balance_due'),
            'total_overdue'   => Invoice::where('status', 'overdue')->sum('balance_due'),
            'draft_count'     => Invoice::where('status', 'draft')->count(),
            'sent_count'      => Invoice::whereIn('status', ['sent', 'partial'])->count(),
            'overdue_count'   => Invoice::where('status', 'overdue')->count(),
            'paid_count'      => Invoice::where('status', 'paid')->count(),
        ];

        // AR Aging buckets (outstanding invoices by days overdue)
        $aging = [
            'current'  => Invoice::whereIn('status', ['sent', 'partial'])->where('due_date', '>=', $now)->sum('balance_due'),
            '1_30'     => Invoice::whereIn('status', ['sent', 'partial', 'overdue'])->whereBetween('due_date', [$now->copy()->subDays(30), $now->copy()->subDay()])->sum('balance_due'),
            '31_60'    => Invoice::whereIn('status', ['sent', 'partial', 'overdue'])->whereBetween('due_date', [$now->copy()->subDays(60), $now->copy()->subDays(31)])->sum('balance_due'),
            '61_90'    => Invoice::whereIn('status', ['sent', 'partial', 'overdue'])->whereBetween('due_date', [$now->copy()->subDays(90), $now->copy()->subDays(61)])->sum('balance_due'),
            '90_plus'  => Invoice::whereIn('status', ['sent', 'partial', 'overdue'])->where('due_date', '<', $now->copy()->subDays(90))->sum('balance_due'),
        ];

        // Monthly revenue trend (last 12 months)
        $trend = Invoice::where('status', 'paid')
            ->where('paid_at', '>=', now()->subMonths(12))
            ->select(DB::raw("TO_CHAR(paid_at, 'YYYY-MM') as month"), DB::raw('SUM(total) as total'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Recent invoices
        $recentInvoices = Invoice::with(['company', 'project', 'proposal'])
            ->orderByDesc('invoice_date')
            ->limit(10)
            ->get();

        // Top clients by outstanding
        $topClients = Invoice::with('company')
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->select('company_id', DB::raw('SUM(balance_due) as outstanding'), DB::raw('COUNT(*) as count'))
            ->groupBy('company_id')
            ->orderByDesc('outstanding')
            ->limit(5)
            ->get();

        return view('invoices.dashboard', compact('kpis', 'aging', 'trend', 'recentInvoices', 'topClients'));
    }

    // ── List ───────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        // Auto-mark overdue
        Invoice::where('status', 'sent')
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        $query = Invoice::with(['project', 'company', 'proposal', 'createdBy'])
            ->orderByDesc('invoice_date');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('company', fn($c) => $c->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('project',  fn($p) => $p->where('title', 'like', "%{$search}%"))
                  ->orWhereHas('proposal', fn($p) => $p->where('title', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->input('type')) {
            $query->where('invoice_type', $type);
        }

        $invoices = $query->paginate(25)->withQueryString();

        $totals = [
            'draft'       => Invoice::where('status', 'draft')->sum('total'),
            'sent'        => Invoice::whereIn('status', ['sent', 'partial', 'overdue'])->sum('balance_due'),
            'overdue'     => Invoice::where('status', 'overdue')->sum('balance_due'),
            'paid'        => Invoice::where('status', 'paid')->sum('total'),
        ];

        return view('invoices.index', compact('invoices', 'totals'));
    }

    // ── Create / Store (manual invoice) ───────────────────────────────────────

    public function create()
    {
        $projects  = Project::with('company')->orderBy('title')->get();
        $companies = Company::where('is_active', 1)->orderBy('name')->get();
        $proposals = Proposal::with('company')->whereNotNull('approved_at')->orderByDesc('id')->get();
        $nextNum   = Invoice::nextNumber();

        return view('invoices.create', compact('projects', 'companies', 'proposals', 'nextNum'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_number' => ['required', 'string', 'max:50', 'unique:invoices,invoice_number'],
            'project_id'     => ['nullable', 'exists:projects,id'],
            'proposal_id'    => ['nullable', 'exists:proposals,id'],
            'company_id'     => ['required', 'exists:companies,id'],
            'invoice_date'   => ['required', 'date'],
            'due_date'       => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'tax_rate'       => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes'          => ['nullable', 'string'],
            'items'          => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity'    => ['required', 'numeric', 'min:0'],
            'items.*.unit_price'  => ['required', 'numeric', 'min:0'],
        ]);

        $invoice = Invoice::create([
            'invoice_number' => $data['invoice_number'],
            'proposal_id'    => $data['proposal_id'] ?? null,
            'project_id'     => $data['project_id'] ?? null,
            'company_id'     => $data['company_id'],
            'invoice_date'   => $data['invoice_date'],
            'due_date'       => $data['due_date'] ?? null,
            'tax_rate'       => $data['tax_rate'] ?? 0,
            'status'         => 'draft',
            'invoice_type'   => 'manual',
            'created_by'     => auth()->id(),
        ]);

        foreach ($data['items'] as $i => $item) {
            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'description' => $item['description'],
                'quantity'    => $item['quantity'],
                'unit_price'  => $item['unit_price'],
                'line_total'  => round($item['quantity'] * $item['unit_price'], 2),
                'sort_order'  => $i,
            ]);
        }

        $invoice->recalculate();
        ActivityLog::record('Created manual invoice', 'invoices', $invoice->id, $invoice->invoice_number);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} created.");
    }

    // ── Show ───────────────────────────────────────────────────────────────────

    public function show(Invoice $invoice)
    {
        $invoice->load(['project', 'company', 'proposal.billingSchedule', 'items', 'payments.recordedBy', 'createdBy', 'billingSchedulePeriod']);
        return view('invoices.show', compact('invoice'));
    }

    // ── Edit / Update ──────────────────────────────────────────────────────────

    public function edit(Invoice $invoice)
    {
        $invoice->load('items');
        $projects  = Project::with('company')->orderBy('title')->get();
        $companies = Company::where('is_active', 1)->orderBy('name')->get();
        $proposals = Proposal::with('company')->whereNotNull('approved_at')->orderByDesc('id')->get();

        return view('invoices.edit', compact('invoice', 'projects', 'companies', 'proposals'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', 'Fully paid invoices cannot be edited.');
        }

        $data = $request->validate([
            'invoice_number' => ['required', 'string', 'max:50', "unique:invoices,invoice_number,{$invoice->id}"],
            'project_id'     => ['nullable', 'exists:projects,id'],
            'proposal_id'    => ['nullable', 'exists:proposals,id'],
            'company_id'     => ['required', 'exists:companies,id'],
            'invoice_date'   => ['required', 'date'],
            'due_date'       => ['nullable', 'date'],
            'tax_rate'       => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes'          => ['nullable', 'string'],
            'items'          => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity'    => ['required', 'numeric', 'min:0'],
            'items.*.unit_price'  => ['required', 'numeric', 'min:0'],
        ]);

        $invoice->update([
            'invoice_number' => $data['invoice_number'],
            'proposal_id'    => $data['proposal_id'] ?? null,
            'project_id'     => $data['project_id'] ?? null,
            'company_id'     => $data['company_id'],
            'invoice_date'   => $data['invoice_date'],
            'due_date'       => $data['due_date'] ?? null,
            'tax_rate'       => $data['tax_rate'] ?? 0,
            'notes'          => $data['notes'] ?? null,
        ]);

        $invoice->items()->delete();
        foreach ($data['items'] as $i => $item) {
            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'description' => $item['description'],
                'quantity'    => $item['quantity'],
                'unit_price'  => $item['unit_price'],
                'line_total'  => round($item['quantity'] * $item['unit_price'], 2),
                'sort_order'  => $i,
            ]);
        }

        $invoice->recalculate();
        ActivityLog::record('Updated invoice', 'invoices', $invoice->id, $invoice->invoice_number);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated.');
    }

    // ── Delete ─────────────────────────────────────────────────────────────────

    public function destroy(Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', 'Paid invoices cannot be deleted.');
        }

        // Unlink from billing period if auto-generated
        if ($invoice->billing_schedule_period_id) {
            $invoice->billingSchedulePeriod?->update([
                'status'         => 'draft',
                'invoice_id'     => null,
                'invoice_number' => null,
                'is_locked'      => false,
            ]);
        }

        $num = $invoice->invoice_number;
        $invoice->delete();
        ActivityLog::record('Deleted invoice', 'invoices', null, $num);

        return redirect()->route('invoices.index')->with('success', "Invoice {$num} deleted.");
    }

    // ── Status Actions ─────────────────────────────────────────────────────────

    public function sendEmail(Invoice $invoice)
    {
        $invoice->update(['status' => 'sent', 'sent_at' => now()]);
        ActivityLog::record('Sent invoice', 'invoices', $invoice->id, $invoice->invoice_number);

        return back()->with('success', "Invoice {$invoice->invoice_number} marked as sent.");
    }

    public function markPaid(Invoice $invoice)
    {
        $invoice->update([
            'status'      => 'paid',
            'paid_at'     => now(),
            'paid_amount' => $invoice->total,
            'balance_due' => 0,
        ]);

        // Sync billing period status
        $invoice->billingSchedulePeriod?->update(['status' => 'paid']);

        ActivityLog::record('Marked invoice paid', 'invoices', $invoice->id, $invoice->invoice_number);

        return back()->with('success', "Invoice {$invoice->invoice_number} marked as paid.");
    }

    public function voidInvoice(Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', 'Cannot void a paid invoice.');
        }

        $invoice->update(['status' => 'void']);

        // Unlink from billing period
        $invoice->billingSchedulePeriod?->update([
            'status'         => 'draft',
            'invoice_id'     => null,
            'invoice_number' => null,
            'is_locked'      => false,
        ]);

        ActivityLog::record('Voided invoice', 'invoices', $invoice->id, $invoice->invoice_number);

        return back()->with('success', "Invoice {$invoice->invoice_number} voided.");
    }

    // ── Payment Application ────────────────────────────────────────────────────

    public function applyPayment(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'payment_date'   => ['required', 'date'],
            'payment_method' => ['required', 'in:check,wire,ach,credit_card,bank_transfer,other'],
            'reference'      => ['nullable', 'string', 'max:100'],
            'notes'          => ['nullable', 'string'],
        ]);

        if ($data['amount'] > $invoice->balance_due + 0.01) {
            return back()->with('error', 'Payment amount exceeds balance due of $' . number_format($invoice->balance_due, 2));
        }

        InvoicePayment::create([
            'invoice_id'     => $invoice->id,
            'amount'         => $data['amount'],
            'payment_date'   => $data['payment_date'],
            'payment_method' => $data['payment_method'],
            'reference'      => $data['reference'] ?? null,
            'notes'          => $data['notes'] ?? null,
            'recorded_by'    => auth()->id(),
        ]);

        $invoice->recalculateBalance();

        // Sync billing period when fully paid
        if ($invoice->status === 'paid') {
            $invoice->billingSchedulePeriod?->update(['status' => 'paid']);
        }

        ActivityLog::record(
            'Applied payment to invoice',
            'invoices',
            $invoice->id,
            "{$invoice->invoice_number} — \${$data['amount']}"
        );

        return back()->with('success', '$' . number_format($data['amount'], 2) . ' payment applied.');
    }

    // ── PDF ────────────────────────────────────────────────────────────────────

    public function generatePdf(Invoice $invoice)
    {
        $invoice->load(['project.company', 'company', 'items', 'createdBy', 'proposal', 'payments']);
        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'))->setPaper('a4', 'portrait');

        return $pdf->download("{$invoice->invoice_number}.pdf");
    }

    public function previewPdf(Invoice $invoice)
    {
        $invoice->load(['project.company', 'company', 'items', 'createdBy', 'proposal', 'payments']);
        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'))->setPaper('a4', 'portrait');

        return $pdf->stream("{$invoice->invoice_number}.pdf");
    }
}
