@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0 fw-700" style="font-size:1rem;">System Settings</h4>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

<form action="{{ route('admin.system-settings.save') }}" method="POST">
    @csrf
    <div class="kore-card mb-4">
        <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">General</div>
        <div class="row g-3">
            <div class="col-sm-6">
                <label class="form-label">Application Name</label>
                <input type="text" name="app_name" class="form-control form-control-sm"
                    value="{{ $settings['app_name'] ?? 'Kore ERP' }}">
            </div>
            <div class="col-sm-3">
                <label class="form-label">Date Format</label>
                <select name="date_format" class="form-select form-select-sm">
                    @foreach(['M/d/Y' => 'Jan 15, 2026', 'm/d/Y' => '01/15/2026', 'd/m/Y' => '15/01/2026', 'Y-m-d' => '2026-01-15'] as $fmt => $ex)
                    <option value="{{ $fmt }}" {{ ($settings['date_format']??'') === $fmt ? 'selected':'' }}>
                        {{ $ex }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label">Time Format</label>
                <select name="time_format" class="form-select form-select-sm">
                    <option value="g:i A" {{ ($settings['time_format']??'') === 'g:i A' ? 'selected':'' }}>12-hour (3:00 PM)</option>
                    <option value="H:i"   {{ ($settings['time_format']??'') === 'H:i'   ? 'selected':'' }}>24-hour (15:00)</option>
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label">Week Start</label>
                <select name="week_start" class="form-select form-select-sm">
                    <option value="Monday" {{ ($settings['week_start']??'') === 'Monday' ? 'selected':'' }}>Monday</option>
                    <option value="Sunday" {{ ($settings['week_start']??'') === 'Sunday' ? 'selected':'' }}>Sunday</option>
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label">Fiscal Year Start</label>
                <select name="fiscal_year_start" class="form-select form-select-sm">
                    @foreach(['01'=>'January','04'=>'April','07'=>'July','10'=>'October'] as $v => $l)
                    <option value="{{ $v }}" {{ ($settings['fiscal_year_start']??'01') === $v ? 'selected':'' }}>
                        {{ $l }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="kore-card mb-4">
        <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">Currency &amp; Invoicing</div>
        <div class="row g-3">
            <div class="col-sm-2">
                <label class="form-label">Symbol</label>
                <input type="text" name="currency_symbol" class="form-control form-control-sm"
                    value="{{ $settings['currency_symbol'] ?? '$' }}" maxlength="5">
            </div>
            <div class="col-sm-3">
                <label class="form-label">Currency Code</label>
                <input type="text" name="currency_code" class="form-control form-control-sm"
                    value="{{ $settings['currency_code'] ?? 'USD' }}" maxlength="5">
            </div>
            <div class="col-sm-3">
                <label class="form-label">Invoice Prefix</label>
                <input type="text" name="invoice_prefix" class="form-control form-control-sm"
                    value="{{ $settings['invoice_prefix'] ?? 'INV-' }}">
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-sm">
        <i class="bi bi-check-lg me-1"></i> Save Settings
    </button>
</form>

@endsection
