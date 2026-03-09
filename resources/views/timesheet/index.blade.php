@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">Timesheet</h4>
        <div style="font-size:0.75rem; color:#6b7280;">
            {{ auth()->user()->full_name }}
            @if($period) · {{ $period->label }} @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('timesheet.history') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-clock-history me-1"></i> History
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger py-2 mb-3" style="font-size:0.8rem;">{{ session('error') }}</div>
@endif

{{-- Period Selector --}}
<div class="kore-card mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-sm-4">
            <label class="form-label" style="font-size:0.75rem; font-weight:600;">Pay Period</label>
            <select name="period_id" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach($periods as $p)
                <option value="{{ $p->id }}"
                    {{ $period && $period->id == $p->id ? 'selected' : '' }}>
                    {{ $p->label }}
                    @if($p->due_date) (Due {{ $p->due_date->format('M d') }}) @endif
                </option>
                @endforeach
            </select>
        </div>
        @if($timesheet)
        <div class="col-sm-auto d-flex align-items-center gap-2">
            @php
                $statusCls = match($timesheet->status) {
                    'submitted' => 'active',
                    'approved'  => 'approved',
                    'rejected'  => 'rejected',
                    default     => 'draft',
                };
            @endphp
            <span class="badge badge-{{ $statusCls }}" style="font-size:0.78rem;">
                {{ ucfirst($timesheet->status) }}
            </span>
            <span style="font-size:0.78rem; color:#6b7280;">
                Total: <strong>{{ number_format($timesheet->total_hours, 2) }} hrs</strong>
            </span>
        </div>
        @endif
    </form>
</div>

@if(! $period)
<div class="kore-card text-center py-5" style="color:#9ca3af;">
    <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
    No timesheet period found for today. Ask your administrator to set up pay periods.
</div>
@else

{{-- Timesheet Grid --}}
<div class="kore-card p-0 mb-3" style="overflow-x:auto;">
    <table class="table mb-0" id="timesheetGrid"
        style="min-width:700px; font-size:0.8rem; border-collapse:collapse;">
        <thead>
            <tr style="background:#f9fafb;">
                <th style="width:200px; padding:10px 14px; border-bottom:1px solid #e5e7eb; font-weight:600; color:#374151;">
                    Project
                </th>
                @foreach($weekDays as $day)
                <th class="text-center" style="padding:8px 4px; border-bottom:1px solid #e5e7eb; min-width:72px;">
                    <div style="font-weight:600; color:#374151;">{{ $day->format('D') }}</div>
                    <div style="font-size:0.7rem; color:#9ca3af;">{{ $day->format('M d') }}</div>
                    @if($day->isWeekend())
                    <div style="font-size:0.65rem; color:#d1d5db;">weekend</div>
                    @endif
                </th>
                @endforeach
                <th class="text-center" style="padding:10px 8px; border-bottom:1px solid #e5e7eb; color:#6b7280; font-weight:600; min-width:58px;">
                    Total
                </th>
            </tr>
        </thead>
        <tbody>
            {{-- Existing project rows --}}
            @foreach($usedProjects as $project)
            @php $rowTotal = 0; @endphp
            <tr class="project-row" data-project-id="{{ $project->id }}">
                <td style="padding:8px 14px; border-bottom:1px solid #f3f4f6; color:#374151;">
                    <div class="fw-500" style="font-size:0.8rem; line-height:1.3;">{{ $project->title }}</div>
                    <div style="font-size:0.7rem; color:#9ca3af;">
                        {{ $project->year }}-{{ str_pad($project->project_number, 3, '0', STR_PAD_LEFT) }}
                    </div>
                </td>
                @foreach($weekDays as $day)
                @php
                    $dateKey = $day->format('Y-m-d');
                    $entry   = $entriesMatrix[$project->id][$dateKey] ?? null;
                    $hrs     = $entry ? (float) $entry->hours : 0;
                    $rowTotal += $hrs;
                @endphp
                <td style="padding:4px 4px; border-bottom:1px solid #f3f4f6; text-align:center;">
                    @if($timesheet->isDraft())
                    <input type="number"
                        class="hour-cell form-control form-control-sm text-center p-1"
                        style="width:64px; margin:0 auto; font-size:0.8rem; border-color:{{ $hrs > 0 ? '#4c8bf5' : '#e5e7eb' }};"
                        min="0" max="24" step="0.25"
                        value="{{ $hrs > 0 ? $hrs : '' }}"
                        placeholder="—"
                        data-period="{{ $period->id }}"
                        data-project="{{ $project->id }}"
                        data-date="{{ $dateKey }}">
                    @else
                    <span style="color:{{ $hrs > 0 ? '#374151' : '#d1d5db' }};">
                        {{ $hrs > 0 ? number_format($hrs, 2) : '—' }}
                    </span>
                    @endif
                </td>
                @endforeach
                <td class="row-total text-center fw-600" style="padding:8px; border-bottom:1px solid #f3f4f6; color:#374151;">
                    {{ $rowTotal > 0 ? number_format($rowTotal, 2) : '—' }}
                </td>
            </tr>
            @endforeach

            {{-- Daily totals row --}}
            <tr style="background:#f9fafb; font-weight:600;">
                <td style="padding:10px 14px; font-size:0.78rem; color:#6b7280;">Daily Total</td>
                @foreach($weekDays as $day)
                @php $dt = $dailyTotals[$day->format('Y-m-d')] ?? 0; @endphp
                <td class="daily-total text-center" data-date="{{ $day->format('Y-m-d') }}"
                    style="padding:10px 4px; font-size:0.8rem; color:{{ $dt > 0 ? '#374151' : '#d1d5db' }};">
                    {{ $dt > 0 ? number_format($dt, 2) : '—' }}
                </td>
                @endforeach
                <td class="text-center grand-total" style="padding:10px 8px; color:#4c8bf5;">
                    {{ number_format($timesheet->total_hours, 2) }}
                </td>
            </tr>
        </tbody>
    </table>
</div>

{{-- Add Project Row --}}
@if($timesheet->isDraft())
<div class="kore-card mb-4">
    <div class="fw-600 mb-2" style="font-size:0.8rem;">Log time for another project</div>
    <div class="d-flex gap-2">
        <select id="addProjectSelect" class="form-select form-select-sm" style="max-width:320px;">
            <option value="">— Select project —</option>
            @foreach($allProjects as $p)
            @php $alreadyShown = $usedProjects->contains('id', $p->id); @endphp
            @if(! $alreadyShown)
            <option value="{{ $p->id }}" data-title="{{ $p->title }}"
                data-ref="{{ $p->year }}-{{ str_pad($p->project_number, 3, '0', STR_PAD_LEFT) }}">
                {{ $p->year }}-{{ str_pad($p->project_number, 3, '0', STR_PAD_LEFT) }} — {{ $p->title }}
            </option>
            @endif
            @endforeach
        </select>
        <button class="btn btn-sm btn-outline-primary" onclick="addProjectRow()">
            <i class="bi bi-plus-lg me-1"></i> Add Row
        </button>
    </div>
</div>
@endif

{{-- Submit / Notes --}}
@if($timesheet->isDraft())
<div class="kore-card">
    <form action="{{ route('timesheet.submit') }}" method="POST" class="d-flex align-items-center gap-3">
        @csrf
        <input type="hidden" name="period_id" value="{{ $period->id }}">
        <button type="submit" class="btn btn-primary btn-sm"
            onclick="return confirm('Submit this timesheet for approval? You will not be able to make changes after submitting.')">
            <i class="bi bi-send me-1"></i> Submit for Approval
        </button>
        <span style="font-size:0.78rem; color:#9ca3af;">
            Make sure all hours are entered before submitting.
        </span>
    </form>
</div>
@elseif($timesheet->isSubmitted())
<div class="alert alert-info py-2" style="font-size:0.8rem;">
    <i class="bi bi-info-circle me-1"></i>
    Submitted {{ $timesheet->submitted_at?->format('M d, Y g:i A') }}. Awaiting approval.
</div>
@endif

@endif {{-- end if $period --}}

@endsection

@push('scripts')
<script>
const PERIOD_ID = {{ $period?->id ?? 'null' }};
const WEEK_DAYS = @json($weekDays->map(fn($d) => $d->format('Y-m-d'))->values());
let saveTimeout = null;

// Auto-save on hour cell change
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('hour-cell')) {
        scheduleAutoSave(e.target);
    }
});
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('hour-cell')) {
        updateRowTotal(e.target);
    }
});

function scheduleAutoSave(input) {
    clearTimeout(saveTimeout);
    saveTimeout = setTimeout(() => saveCell(input), 600);
}

function saveCell(input) {
    const hours     = parseFloat(input.value) || 0;
    const projectId = input.dataset.project;
    const entryDate = input.dataset.date;

    input.style.borderColor = '#f59e0b'; // saving indicator

    fetch('{{ route("timesheet.entry.save") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            period_id:  PERIOD_ID,
            project_id: projectId,
            entry_date: entryDate,
            hours:      hours,
            entry_type: 'billable'
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            input.style.borderColor = hours > 0 ? '#22c55e' : '#e5e7eb';
            updateGrandTotal(data.total);
            updateDailyTotal(entryDate);
        } else {
            input.style.borderColor = '#ef4444';
        }
    })
    .catch(() => { input.style.borderColor = '#ef4444'; });
}

function updateRowTotal(input) {
    const row   = input.closest('tr');
    const cells = row.querySelectorAll('.hour-cell');
    let total   = 0;
    cells.forEach(c => { total += parseFloat(c.value) || 0; });
    const totalCell = row.querySelector('.row-total');
    if (totalCell) totalCell.textContent = total > 0 ? total.toFixed(2) : '—';
}

function updateDailyTotal(date) {
    const allForDate = document.querySelectorAll(`.hour-cell[data-date="${date}"]`);
    let total = 0;
    allForDate.forEach(c => { total += parseFloat(c.value) || 0; });
    const cell = document.querySelector(`.daily-total[data-date="${date}"]`);
    if (cell) {
        cell.textContent = total > 0 ? total.toFixed(2) : '—';
        cell.style.color = total > 0 ? '#374151' : '#d1d5db';
    }
}

function updateGrandTotal(total) {
    const gt = document.querySelector('.grand-total');
    if (gt) gt.textContent = parseFloat(total).toFixed(2);
}

function addProjectRow() {
    const sel    = document.getElementById('addProjectSelect');
    const opt    = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;

    const projectId = opt.value;
    const title     = opt.dataset.title;
    const ref       = opt.dataset.ref;

    // Don't add duplicate
    if (document.querySelector(`.project-row[data-project-id="${projectId}"]`)) {
        sel.value = '';
        return;
    }

    const tbody = document.querySelector('#timesheetGrid tbody');
    const totalsRow = tbody.querySelector('tr:last-child');

    const tr = document.createElement('tr');
    tr.className = 'project-row';
    tr.dataset.projectId = projectId;

    let html = `<td style="padding:8px 14px; border-bottom:1px solid #f3f4f6; color:#374151;">
        <div class="fw-500" style="font-size:0.8rem;">${title}</div>
        <div style="font-size:0.7rem; color:#9ca3af;">${ref}</div>
    </td>`;

    WEEK_DAYS.forEach(date => {
        html += `<td style="padding:4px 4px; border-bottom:1px solid #f3f4f6; text-align:center;">
            <input type="number" class="hour-cell form-control form-control-sm text-center p-1"
                style="width:64px; margin:0 auto; font-size:0.8rem; border-color:#e5e7eb;"
                min="0" max="24" step="0.25" placeholder="—"
                data-period="${PERIOD_ID}" data-project="${projectId}" data-date="${date}">
        </td>`;
    });

    html += `<td class="row-total text-center fw-600" style="padding:8px; border-bottom:1px solid #f3f4f6; color:#d1d5db;">—</td>`;
    tr.innerHTML = html;

    tbody.insertBefore(tr, totalsRow);

    // Remove from dropdown
    opt.remove();
    sel.value = '';
}
</script>
@endpush
