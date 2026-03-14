@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">Import Project Template</h4>
        <div style="font-size:0.75rem; color:#6b7280;">Upload an Excel or Google Sheets file to create a template</div>
    </div>
    <a href="{{ route('admin.templates') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Templates
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger py-2 mb-3" style="font-size:0.8rem;">
    <strong>Import failed:</strong>
    <ul class="mb-0 mt-1">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<div class="row g-3">

    {{-- Upload Form --}}
    <div class="col-lg-7">
        <div class="kore-card">
            <div class="fw-600 mb-3" style="font-size:0.85rem; color:#374151;">
                <i class="bi bi-file-earmark-arrow-up me-2 text-primary"></i>Upload Spreadsheet
            </div>

            <form method="POST" action="{{ route('admin.templates.import') }}" enctype="multipart/form-data">
                @csrf

                {{-- File picker --}}
                <div class="mb-3">
                    <label class="form-label" style="font-size:0.75rem; font-weight:600; color:#374151;">
                        File <span class="text-danger">*</span>
                        <span style="font-weight:400; color:#9ca3af;">— .xlsx, .xls, or .csv</span>
                    </label>
                    <div id="dropZone" class="import-drop-zone" onclick="document.getElementById('fileInput').click()">
                        <i class="bi bi-cloud-arrow-up" style="font-size:2rem; color:#9ca3af;"></i>
                        <div class="mt-2" style="font-size:0.82rem; color:#6b7280;">
                            Drag & drop here, or <span style="color:#2563eb; font-weight:600;">click to browse</span>
                        </div>
                        <div style="font-size:0.72rem; color:#9ca3af; margin-top:4px;">Supports .xlsx · .xls · .csv — max 10 MB</div>
                        <div id="fileName" class="mt-2" style="font-size:0.78rem; font-weight:600; color:#374151; display:none;"></div>
                    </div>
                    <input type="file" id="fileInput" name="file" accept=".xlsx,.xls,.csv"
                        class="d-none @error('file') is-invalid @enderror" required>
                    @error('file')<div class="text-danger mt-1" style="font-size:0.72rem;">{{ $message }}</div>@enderror
                </div>

                <hr style="border-color:#f3f4f6; margin:16px 0;">

                {{-- Template metadata --}}
                <div class="fw-600 mb-2" style="font-size:0.78rem; color:#374151;">Template Details</div>

                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label" style="font-size:0.73rem; font-weight:600; color:#374151;">
                            Template Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror"
                            value="{{ old('name') }}" placeholder="e.g. Civil Engineering Standard" required>
                        @error('name')<div class="invalid-feedback" style="font-size:0.72rem;">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-sm-6">
                        <label class="form-label" style="font-size:0.73rem; font-weight:600; color:#374151;">Billing Type</label>
                        <select name="billing_type" id="impBillingType" class="form-select form-select-sm">
                            <option value="">— Any / Not specified —</option>
                            @foreach($billingTypes as $key => $label)
                            <option value="{{ $key }}" @selected(old('billing_type') == $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-sm-6">
                        <label class="form-label" style="font-size:0.73rem; font-weight:600; color:#374151;">Billing Cycle</label>
                        <select name="billing_cycle" class="form-select form-select-sm">
                            <option value="">— Not specified —</option>
                            @foreach($billingCycles as $key => $label)
                            <option value="{{ $key }}" @selected(old('billing_cycle') == $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label" style="font-size:0.73rem; font-weight:600; color:#374151;">Work Type</label>
                        <select name="work_type_id" class="form-select form-select-sm">
                            <option value="">— Any —</option>
                            @foreach($workTypes as $wt)
                            <option value="{{ $wt->id }}" @selected(old('work_type_id') == $wt->id)>{{ $wt->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label" style="font-size:0.73rem; font-weight:600; color:#374151;">Description</label>
                        <textarea name="description" class="form-control form-control-sm" rows="2"
                            placeholder="Optional">{{ old('description') }}</textarea>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary btn-sm" id="importBtn">
                        <i class="bi bi-upload me-1"></i>Import Template
                    </button>
                    <a href="{{ route('admin.templates') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Help panel --}}
    <div class="col-lg-5">

        {{-- Download sample --}}
        <div class="kore-card mb-3" style="background:#eff6ff; border:1px solid #bfdbfe;">
            <div class="fw-600 mb-1" style="font-size:0.82rem; color:#1e40af;">
                <i class="bi bi-file-earmark-spreadsheet me-2"></i>Download Sample File
            </div>
            <div style="font-size:0.78rem; color:#1e40af; opacity:0.85; margin-bottom:10px;">
                Get a pre-filled Excel file that shows exactly the expected format. Open it in Excel or Google Sheets, edit the data, then upload it back here.
            </div>
            <a href="{{ route('admin.templates.import.sample') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-download me-1"></i>Download Sample (.xlsx)
            </a>
        </div>

        {{-- Google Sheets tip --}}
        <div class="kore-card mb-3" style="background:#fefce8; border:1px solid #fde68a;">
            <div class="fw-600 mb-1" style="font-size:0.8rem; color:#92400e;">
                <i class="bi bi-google me-2"></i>Using Google Sheets?
            </div>
            <div style="font-size:0.77rem; color:#78350f; line-height:1.55;">
                Open your Google Sheet &rarr; <strong>File</strong> &rarr; <strong>Download</strong> &rarr; <strong>Microsoft Excel (.xlsx)</strong>, then upload that file here.
            </div>
        </div>

        {{-- Column reference --}}
        <div class="kore-card">
            <div class="fw-600 mb-2" style="font-size:0.82rem; color:#374151;">
                <i class="bi bi-table me-1"></i>Column Reference
            </div>
            <div class="table-responsive">
            <table class="table table-sm" style="font-size:0.72rem;">
                <thead>
                    <tr style="background:#f9fafb;">
                        <th style="width:32px; color:#9ca3af;">#</th>
                        <th style="color:#374151;">Column</th>
                        <th style="color:#374151;">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td class="text-muted">A</td><td class="fw-600">ID</td><td class="text-muted">Unique row ID you assign (e.g. D1, M1, T1). Used for Parent ID and Depends On.</td></tr>
                    <tr><td class="text-muted">B</td><td class="fw-600">Type</td><td class="text-muted"><code>DELIVERABLE</code>, <code>MILESTONE</code>, or <code>TASK</code></td></tr>
                    <tr><td class="text-muted">C</td><td class="fw-600 text-danger">Name *</td><td class="text-muted">Required. Name of the item.</td></tr>
                    <tr><td class="text-muted">D</td><td class="fw-600">Description</td><td class="text-muted">Optional free text.</td></tr>
                    <tr><td class="text-muted">E</td><td class="fw-600">Parent ID</td><td class="text-muted">MILESTONE → parent DELIVERABLE ID. TASK → parent MILESTONE (or DELIVERABLE) ID.</td></tr>
                    <tr><td class="text-muted">F</td><td class="fw-600">Depends On</td><td class="text-muted">ID of predecessor (same type). Multiple IDs comma-separated; first is used.</td></tr>
                    <tr><td class="text-muted">G</td><td class="fw-600">Budget Hours</td><td class="text-muted">Number. Milestone = budgeted hrs. Task = estimated hrs.</td></tr>
                    <tr><td class="text-muted">H</td><td class="fw-600">Assigned Role</td><td class="text-muted">e.g. "Engineer" or "Project Manager"</td></tr>
                    <tr><td class="text-muted">I</td><td class="fw-600">Start Day</td><td class="text-muted">Relative start day from project start (integer). Milestones & Tasks.</td></tr>
                    <tr><td class="text-muted">J</td><td class="fw-600">End Day</td><td class="text-muted">Relative end day from project start (integer). Milestones & Tasks.</td></tr>
                </tbody>
            </table>
            </div>

            <div style="font-size:0.72rem; color:#6b7280; border-top:1px solid #f3f4f6; padding-top:8px; margin-top:4px;">
                <strong>Row 1 is the header</strong> and is always skipped. Rows with a blank Name are skipped. Every DELIVERABLE row must appear <em>before</em> its child MILESTONE rows, and every MILESTONE before its TASK rows.
            </div>
        </div>

    </div>
</div>

@push('styles')
<style>
.import-drop-zone {
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    padding: 28px 20px;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
    background: #fafafa;
}
.import-drop-zone:hover,
.import-drop-zone.drag-over {
    border-color: #2563eb;
    background: #eff6ff;
}
</style>
@endpush

@push('scripts')
<script>
const dropZone  = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const fileLabel = document.getElementById('fileName');

fileInput.addEventListener('change', () => {
    if (fileInput.files[0]) {
        fileLabel.textContent = fileInput.files[0].name;
        fileLabel.style.display = 'block';
    }
});

dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) {
        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;
        fileLabel.textContent = file.name;
        fileLabel.style.display = 'block';
    }
});

document.querySelector('form').addEventListener('submit', function() {
    const btn = document.getElementById('importBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Importing…';
});
</script>
@endpush

@endsection
