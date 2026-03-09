@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">Project Schedule</h4>
        <div style="font-size:0.75rem; color:#6b7280;">{{ $projects->count() }} active projects</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('schedule.employee') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-people me-1"></i> By Employee
        </a>
        <a href="{{ route('schedule.resources') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-bar-chart me-1"></i> Resources
        </a>
    </div>
</div>

{{-- Gantt Container --}}
<div class="kore-card p-0 mb-4">
    <div class="d-flex justify-content-between align-items-center px-4 py-3"
        style="border-bottom:1px solid #f3f4f6;">
        <div style="font-size:0.78rem; font-weight:600; color:#6b7280;">
            PROJECT / DELIVERABLE / TASK
        </div>
        <div style="font-size:0.75rem; color:#9ca3af;">
            <span class="me-3"><span style="display:inline-block;width:10px;height:10px;background:#4c8bf5;border-radius:2px;"></span> Project</span>
            <span class="me-3"><span style="display:inline-block;width:10px;height:10px;background:#f59e0b;border-radius:2px;"></span> In Progress</span>
            <span class="me-3"><span style="display:inline-block;width:10px;height:10px;background:#22c55e;border-radius:2px;"></span> Complete</span>
            <span><span style="display:inline-block;width:10px;height:10px;background:#d1d5db;border-radius:2px;"></span> Pending</span>
        </div>
    </div>
    <div id="ganttContainer" style="overflow-x:auto; min-height:400px; padding:16px;">
        <div id="ganttLoading" class="text-center py-5" style="color:#9ca3af;">
            <div class="spinner-border spinner-border-sm me-2"></div> Loading schedule...
        </div>
        <div id="ganttChart" style="display:none;"></div>
    </div>
</div>

{{-- Project List fallback / quick view --}}
@foreach($projects as $project)
<div class="kore-card mb-2">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <a href="{{ route('projects.show', $project) }}"
                class="fw-600 text-decoration-none" style="font-size:0.88rem; color:#374151;">
                {{ $project->title }}
            </a>
            <div style="font-size:0.75rem; color:#9ca3af;">
                {{ $project->company?->name ?? '' }}
                @if($project->projectManager) · PM: {{ $project->projectManager->full_name }} @endif
            </div>
        </div>
        <div class="text-end">
            <div style="font-size:0.75rem; color:#6b7280;">
                @if($project->start_date)
                    {{ $project->start_date->format('M d, Y') }}
                    @if($project->end_date) → {{ $project->end_date->format('M d, Y') }} @endif
                @else
                    <span style="color:#d1d5db;">No dates set</span>
                @endif
            </div>
            @php
                $tasks    = $project->deliverables->flatMap(fn($d) => $d->milestones)->flatMap(fn($m) => $m->tasks);
                $done     = $tasks->where('status', 'complete')->count();
                $total    = $tasks->count();
                $pct      = $total ? round($done / $total * 100) : 0;
            @endphp
            <div class="progress mt-1" style="height:4px; width:120px;">
                <div class="progress-bar bg-primary" style="width:{{ $pct }}%"></div>
            </div>
            <div style="font-size:0.7rem; color:#9ca3af; margin-top:2px;">{{ $pct }}% complete</div>
        </div>
    </div>
</div>
@endforeach

@if($projects->isEmpty())
<div class="kore-card text-center py-5" style="color:#9ca3af;">
    <i class="bi bi-calendar3 fs-2 d-block mb-2"></i>
    No active projects with dates. Add start/end dates to projects to see the schedule.
</div>
@endif

@endsection

@push('scripts')
<script>
// Simple Gantt renderer using SVG-like divs
fetch('{{ route("api.schedule.gantt") }}')
    .then(r => r.json())
    .then(bars => {
        document.getElementById('ganttLoading').style.display = 'none';
        if (!bars.length) return;

        const projects = bars.filter(b => b.type === 'project' && b.start);
        if (!projects.length) return;

        const allDates = projects.flatMap(p => [new Date(p.start), new Date(p.end)]).filter(Boolean);
        const minDate  = new Date(Math.min(...allDates));
        const maxDate  = new Date(Math.max(...allDates));
        const totalDays= Math.max(1, Math.ceil((maxDate - minDate) / 86400000)) + 14;

        const container = document.getElementById('ganttChart');
        container.style.display = 'block';

        const barWidth = Math.max(800, totalDays * 14);

        let html = `<div style="display:flex; gap:0;">`;
        // Labels
        html += `<div style="width:220px; flex-shrink:0;">`;
        projects.forEach(p => {
            html += `<div style="height:32px; line-height:32px; padding:0 8px; font-size:0.78rem; font-weight:600; color:#374151; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; border-bottom:1px solid #f3f4f6;">${p.label}</div>`;
        });
        html += `</div>`;
        // Bars
        html += `<div style="flex:1; overflow-x:auto; position:relative;" id="ganttBars">`;
        const pxPerDay = 14;
        projects.forEach(p => {
            const start = new Date(p.start);
            const end   = new Date(p.end);
            const left  = Math.max(0, Math.ceil((start - minDate) / 86400000)) * pxPerDay;
            const width = Math.max(20, Math.ceil((end - start) / 86400000)) * pxPerDay;
            html += `<div style="height:32px; border-bottom:1px solid #f3f4f6; position:relative; min-width:${barWidth}px;">`;
            html += `<div style="position:absolute; left:${left}px; top:6px; width:${width}px; height:20px; background:${p.color}; border-radius:4px; display:flex; align-items:center; padding:0 8px;">`;
            html += `<span style="font-size:0.7rem; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${p.label}</span>`;
            html += `</div></div>`;
        });
        html += `</div></div>`;

        container.innerHTML = html;
    })
    .catch(() => {
        document.getElementById('ganttLoading').textContent = 'Could not load Gantt data.';
    });
</script>
@endpush
