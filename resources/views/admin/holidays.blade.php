@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0 fw-700" style="font-size:1rem;">Holidays</h4>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

<div class="row g-4">
    <div class="col-lg-7">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div style="font-size:0.85rem; font-weight:600;">{{ $year }}</div>
            <form method="GET" class="d-flex gap-2 align-items-center">
                <select name="year" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                    @foreach(range(now()->year + 1, now()->year - 3) as $y)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected':'' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="kore-card p-0">
            <table class="table kore-table mb-0">
                <thead>
                    <tr><th>Holiday</th><th>Date</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($holidays as $h)
                    <tr>
                        <td class="fw-500" style="font-size:0.82rem;">{{ $h->name }}</td>
                        <td style="font-size:0.8rem; color:#6b7280;">{{ $h->holiday_date->format('D, M d, Y') }}</td>
                        <td class="text-end">
                            <form action="{{ route('admin.holidays.destroy', $h) }}" method="POST"
                                onsubmit="return confirm('Remove this holiday?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light text-danger">
                                    <i class="bi bi-x"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center py-4" style="color:#9ca3af; font-size:0.8rem;">
                            No holidays for {{ $year }}.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="kore-card">
            <div class="fw-600 mb-3" style="font-size:0.85rem;">Add Holiday</div>
            <form action="{{ route('admin.holidays.store') }}" method="POST">
                @csrf
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.78rem;">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control form-control-sm" required
                        placeholder="e.g. Christmas Day">
                </div>
                <div class="mb-3">
                    <label class="form-label" style="font-size:0.78rem;">Date <span class="text-danger">*</span></label>
                    <input type="date" name="holiday_date" class="form-control form-control-sm" required>
                </div>
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-plus-lg me-1"></i> Add Holiday
                </button>
            </form>
        </div>
    </div>
</div>

@endsection
