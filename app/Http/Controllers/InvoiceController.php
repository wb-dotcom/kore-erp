<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['project', 'company', 'createdBy'])
            ->orderByDesc('invoice_date');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('company', fn($c) => $c->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('project', fn($p) => $p->where('title', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Auto-mark overdue
        Invoice::where('status', 'sent')
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        $invoices = $query->paginate(25)->withQueryString();

        $totals = [
            'draft'   => Invoice::where('status', 'draft')->sum('total'),
            'sent'    => Invoice::whereIn('status', ['sent', 'overdue'])->sum('total'),
            'overdue' => Invoice::where('status', 'overdue')->sum('total'),
            'paid'    => Invoice::where('status', 'paid')->sum('total'),
        ];

        return view('invoices.index', compact('invoices', 'totals'));
    }

    public function create()
    {
        $projects  = Project::with('company')->orderBy('title')->get();
        $companies = Company::where('is_active', 1)->orderBy('name')->get();

        $lastNumber = Invoice::max('invoice_number');
        $prefix     = config('kore.invoice_prefix', 'INV-');
        $nextNum    = $lastNumber
            ? $prefix . str_pad((int) str_replace($prefix, '', $lastNumber) + 1, 4, '0', STR_PAD_LEFT)
            : $prefix . '0001';

        return view('invoices.create', compact('projects', 'companies', 'nextNum'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_number' => ['required', 'string', 'max:50', 'unique:invoices,invoice_number'],
            'project_id'     => ['required', 'exists:projects,id'],
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
            'project_id'     => $data['project_id'],
            'company_id'     => $data['company_id'],
            'invoice_date'   => $data['invoice_date'],
            'due_date'       => $data['due_date'] ?? null,
            'tax_rate'       => $data['tax_rate'] ?? 0,
            'status'         => 'draft',
            'created_by'     => auth()->id(),
        ]);

        foreach ($data['items'] as $i => $item) {
            $lineTotal = round($item['quantity'] * $item['unit_price'], 2);
            InvoiceItem::create([
                'invoice_id'   => $invoice->id,
                'description'  => $item['description'],
                'quantity'     => $item['quantity'],
                'unit_price'   => $item['unit_price'],
                'line_total'   => $lineTotal,
                'sort_order'   => $i,
            ]);
        }

        $invoice->recalculate();

        ActivityLog::record('Created invoice', 'invoices', $invoice->id, $invoice->invoice_number);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} created.");
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['project', 'company', 'items', 'createdBy']);
        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load('items');
        $projects  = Project::with('company')->orderBy('title')->get();
        $companies = Company::where('is_active', 1)->orderBy('name')->get();

        return view('invoices.edit', compact('invoice', 'projects', 'companies'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', 'Paid invoices cannot be edited.');
        }

        $data = $request->validate([
            'invoice_number' => ['required', 'string', 'max:50', "unique:invoices,invoice_number,{$invoice->id}"],
            'project_id'     => ['required', 'exists:projects,id'],
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
            'project_id'     => $data['project_id'],
            'company_id'     => $data['company_id'],
            'invoice_date'   => $data['invoice_date'],
            'due_date'       => $data['due_date'] ?? null,
            'tax_rate'       => $data['tax_rate'] ?? 0,
            'notes'          => $data['notes'] ?? null,
        ]);

        // Rebuild items
        $invoice->items()->delete();
        foreach ($data['items'] as $i => $item) {
            $lineTotal = round($item['quantity'] * $item['unit_price'], 2);
            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'description' => $item['description'],
                'quantity'    => $item['quantity'],
                'unit_price'  => $item['unit_price'],
                'line_total'  => $lineTotal,
                'sort_order'  => $i,
            ]);
        }

        $invoice->recalculate();
        ActivityLog::record('Updated invoice', 'invoices', $invoice->id, $invoice->invoice_number);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice updated.');
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', 'Paid invoices cannot be deleted.');
        }
        $num = $invoice->invoice_number;
        $invoice->delete();
        ActivityLog::record('Deleted invoice', 'invoices', null, $num);

        return redirect()->route('invoices.index')->with('success', "Invoice {$num} deleted.");
    }

    public function generatePdf(Invoice $invoice)
    {
        $invoice->load(['project.company', 'company', 'items', 'createdBy']);
        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("{$invoice->invoice_number}.pdf");
    }

    public function sendEmail(Invoice $invoice)
    {
        // Basic implementation — extend with Mailable in production
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
        ]);
        ActivityLog::record('Marked invoice paid', 'invoices', $invoice->id, $invoice->invoice_number);

        return back()->with('success', "Invoice {$invoice->invoice_number} marked as paid.");
    }
}
