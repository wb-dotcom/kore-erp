@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">Project Templates</h4>
        <div style="font-size:0.75rem; color:#6b7280;">Reusable deliverable / milestone / task structures</div>
    </div>
    <a href="{{ route('admin.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Admin
    </a>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger py-2 mb-3" style="font-size:0.8rem;">{{ session('error') }}</div>
@endif

<div class="row g-3">

    {{-- Template List --}}
    <div class="col-lg-8">
        <div class="kore-card p-0">
            <div class="d-flex align-items-center justify-content-between px-3 py-2" style="border-bottom:1px solid #f3f4f6;">
                <span class="fw-600" style="font-size:0.82rem;">Templates ({{ $templates->count() }})</span>
            </div>
            @forelse($templates as $tpl)
            <div class="d-flex align-items-center gap-2 px-3 py-2" style="border-bottom:1px solid #f9fafb;">
                <div style="flex:1; min-width:0;">
                    <a href="{{ route('admin.templates.show', $tpl) }}" class="fw-600 text-decoration-none"
                        style="font-size:0.85rem; color:#111827;">{{ $tpl->name }}</a>
                    @if($tpl->workType)
                    <span class="badge ms-1" style="background:#eff6ff; color:#2563eb; font-size:0.7rem; font-weight:500;">
                        {{ $tpl->workType->name }}
                    </span>
                    @endif
                    @if($tpl->description)
                    <div style="font-size:0.75rem; color:#6b7280; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        {{ $tpl->description }}
                    </div>
                    @endif
                </div>
                <div class="text-center" style="min-width:60px;">
                    <div class="fw-600" style="font-size:0.85rem;">{{ number_format($tpl->total_budgeted_hours, 1) }}</div>
                    <div style="font-size:0.7rem; color:#9ca3af;">hrs</div>
                </div>
                <div class="text-center" style="min-width:50px;">
                    <div class="fw-600" style="font-size:0.85rem;">{{ $tpl->deliverables_count }}</div>
                    <div style="font-size:0.7rem; color:#9ca3af;">deliverables</div>
                </div>
                <div class="d-flex gap-1">
                    <a href="{{ route('admin.templates.show', $tpl) }}"
                        class="btn btn-xs btn-outline-primary" style="font-size:0.72rem; padding:2px 8px;">
                        Edit
                    </a>
                    <form method="POST" action="{{ route('admin.templates.destroy', $tpl) }}"
                        onsubmit="return confirm('Delete template \'{{ addslashes($tpl->name) }}\'?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-xs btn-outline-danger" style="font-size:0.72rem; padding:2px 8px;">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="text-center py-4" style="font-size:0.82rem; color:#9ca3af;">
                No templates yet. Create your first one.
            </div>
            @endforelse
        </div>
    </div>

    {{-- Create Form --}}
    <div class="col-lg-4">
        <div class="kore-card">
            <div class="fw-600 mb-3" style="font-size:0.82rem; color:#374151;">New Template</div>
            <form method="POST" action="{{ route('admin.templates.store') }}">
                @csrf
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.75rem; font-weight:600; color:#374151;">Name *</label>
                    <input type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror"
                        value="{{ old('name') }}" placeholder="e.g. Civil Engineering Standard" required>
                    @error('name')<div class="invalid-feedback" style="font-size:0.72rem;">{{ $message }}</div>@enderror
                </div>
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.75rem; font-weight:600; color:#374151;">Work Type</label>
                    <select name="work_type_id" class="form-select form-select-sm">
                        <option value="">— Any —</option>
                        @foreach(\App\Models\WorkType::orderBy('name')->get() as $wt)
                        <option value="{{ $wt->id }}" @selected(old('work_type_id') == $wt->id)>{{ $wt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="font-size:0.75rem; font-weight:600; color:#374151;">Description</label>
                    <textarea name="description" class="form-control form-control-sm" rows="2"
                        placeholder="Optional description">{{ old('description') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-sm w-100">Create Template</button>
            </form>
        </div>
    </div>

</div>

@endsection
