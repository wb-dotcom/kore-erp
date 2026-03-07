@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">Calendar</h4>
        <div style="font-size:0.75rem; color:#6b7280;">Events &amp; schedule</div>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addEventModal">
        <i class="bi bi-plus-lg me-1"></i> Add Event
    </button>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

<div class="kore-card">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
    <div id="calendar"></div>
</div>

{{-- Add Event Modal --}}
<div class="modal fade" id="addEventModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-600">Add Event</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <form id="addEventForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label" style="font-size:0.78rem;">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" style="font-size:0.78rem;">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" id="eventStart" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" style="font-size:0.78rem;">End Date</label>
                        <input type="date" name="end_date" class="form-control form-control-sm">
                    </div>
                    <div class="mb-2">
                        <label class="form-label" style="font-size:0.78rem;">Color</label>
                        <select name="color" class="form-select form-select-sm">
                            <option value="#4c8bf5">Blue</option>
                            <option value="#22c55e">Green</option>
                            <option value="#f59e0b">Yellow</option>
                            <option value="#ef4444">Red</option>
                            <option value="#8b5cf6">Purple</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Add Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listMonth' },
        height: 'auto',
        events: '{{ route("events.index") }}?json=1',
        dateClick: function(info) {
            document.getElementById('eventStart').value = info.dateStr;
            new bootstrap.Modal(document.getElementById('addEventModal')).show();
        },
        eventClick: function(info) {
            alert(info.event.title);
        }
    });
    calendar.render();
});

document.getElementById('addEventForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fetch('{{ route("events.store") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
        body: fd,
    })
    .then(r => r.json())
    .then(() => { bootstrap.Modal.getInstance(document.getElementById('addEventModal')).hide(); window.location.reload(); });
});
</script>
@endpush
