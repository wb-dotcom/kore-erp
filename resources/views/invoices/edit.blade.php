@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0 fw-700" style="font-size:1rem;">Edit — {{ $invoice->invoice_number }}</h4>
</div>

@if($errors->any())
<div class="alert alert-danger py-2" style="font-size:0.8rem;">
    <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form action="{{ route('invoices.update', $invoice) }}" method="POST" id="invoiceForm">
    @csrf @method('PUT')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="kore-card mb-4">
                <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">Invoice Details</div>
                <div class="row g-3">
                    <div class="col-sm-3">
                        <label class="form-label">Invoice # <span class="text-danger">*</span></label>
                        <input type="text" name="invoice_number" class="form-control form-control-sm"
                            value="{{ old('invoice_number', $invoice->invoice_number) }}" required>
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                        <input type="date" name="invoice_date" class="form-control form-control-sm"
                            value="{{ old('invoice_date', $invoice->invoice_date->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control form-control-sm"
                            value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Tax Rate (%)</label>
                        <input type="number" name="tax_rate" id="taxRate" step="0.01" class="form-control form-control-sm"
                            value="{{ old('tax_rate', $invoice->tax_rate) }}" min="0" max="100" oninput="recalc()">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Project <span class="text-danger">*</span></label>
                        <select name="project_id" class="form-select form-select-sm" required>
                            <option value="">— Select project —</option>
                            @foreach($projects as $p)
                            <option value="{{ $p->id }}" {{ old('project_id', $invoice->project_id) == $p->id ? 'selected' : '' }}>
                                {{ $p->title }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Bill To <span class="text-danger">*</span></label>
                        <select name="company_id" class="form-select form-select-sm" required>
                            <option value="">— Select company —</option>
                            @foreach($companies as $c)
                            <option value="{{ $c->id }}" {{ old('company_id', $invoice->company_id) == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2">{{ old('notes', $invoice->notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="kore-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="fw-600" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">Line Items</div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addItem()">
                        <i class="bi bi-plus-sm me-1"></i> Add Line
                    </button>
                </div>
                <table class="table mb-2" style="font-size:0.8rem;" id="itemsTable">
                    <thead>
                        <tr style="background:#f9fafb;">
                            <th style="width:50%;">Description</th>
                            <th style="width:12%;">Qty</th>
                            <th style="width:15%;">Unit Price</th>
                            <th style="width:15%;" class="text-end">Total</th>
                            <th style="width:8%;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        @foreach($invoice->items as $i => $item)
                        <tr class="item-row">
                            <td><input type="text" name="items[{{ $i }}][description]" class="form-control form-control-sm" required value="{{ $item->description }}"></td>
                            <td><input type="number" name="items[{{ $i }}][quantity]" class="form-control form-control-sm qty" value="{{ $item->quantity }}" min="0" step="0.01" oninput="recalcRow(this)"></td>
                            <td><input type="number" name="items[{{ $i }}][unit_price]" class="form-control form-control-sm price" value="{{ $item->unit_price }}" min="0" step="0.01" oninput="recalcRow(this)"></td>
                            <td class="text-end fw-600 line-total align-middle">${{ number_format($item->line_total, 2) }}</td>
                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-sm btn-light text-danger" onclick="removeItem(this)">
                                    <i class="bi bi-x"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="d-flex justify-content-end">
                    <div style="width:240px; font-size:0.82rem;">
                        <div class="d-flex justify-content-between py-1 border-top">
                            <span style="color:#6b7280;">Subtotal</span>
                            <span class="fw-600" id="subtotalDisplay">${{ number_format($invoice->subtotal, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span style="color:#6b7280;">Tax (<span id="taxRateDisplay">{{ number_format($invoice->tax_rate, 2) }}</span>%)</span>
                            <span class="fw-600" id="taxDisplay">${{ number_format($invoice->tax_amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-top fw-700" style="font-size:0.9rem;">
                            <span>Total</span>
                            <span id="totalDisplay">${{ number_format($invoice->total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-check-lg me-1"></i> Save Changes
        </button>
        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
    </div>
</form>

@endsection

@push('scripts')
<script>
let itemIndex = {{ $invoice->items->count() }};
function addItem() {
    const tbody = document.getElementById('itemsBody');
    const row   = document.createElement('tr');
    row.className = 'item-row';
    row.innerHTML = `<td><input type="text" name="items[${itemIndex}][description]" class="form-control form-control-sm" required placeholder="Description"></td><td><input type="number" name="items[${itemIndex}][quantity]" class="form-control form-control-sm qty" value="1" min="0" step="0.01" oninput="recalcRow(this)"></td><td><input type="number" name="items[${itemIndex}][unit_price]" class="form-control form-control-sm price" value="0.00" min="0" step="0.01" oninput="recalcRow(this)"></td><td class="text-end fw-600 line-total align-middle">$0.00</td><td class="text-center align-middle"><button type="button" class="btn btn-sm btn-light text-danger" onclick="removeItem(this)"><i class="bi bi-x"></i></button></td>`;
    tbody.appendChild(row);
    itemIndex++;
}
function removeItem(btn) { if(document.querySelectorAll('.item-row').length>1){btn.closest('tr').remove();recalc();} }
function recalcRow(i) { const r=i.closest('tr');const q=parseFloat(r.querySelector('.qty').value)||0;const p=parseFloat(r.querySelector('.price').value)||0;r.querySelector('.line-total').textContent='$'+(q*p).toFixed(2);recalc(); }
function recalc() { let s=0;document.querySelectorAll('.item-row').forEach(r=>{s+=(parseFloat(r.querySelector('.qty')?.value)||0)*(parseFloat(r.querySelector('.price')?.value)||0);});const t=parseFloat(document.getElementById('taxRate').value)||0;const ta=s*t/100;document.getElementById('subtotalDisplay').textContent='$'+s.toFixed(2);document.getElementById('taxRateDisplay').textContent=t.toFixed(2);document.getElementById('taxDisplay').textContent='$'+ta.toFixed(2);document.getElementById('totalDisplay').textContent='$'+(s+ta).toFixed(2); }
</script>
@endpush
