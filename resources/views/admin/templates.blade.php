@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0 fw-700" style="font-size:1rem;">System Templates</h4>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

<div class="kore-card">
    <p style="font-size:0.82rem; color:#6b7280; margin-bottom:20px;">
        Manage reusable templates for proposals, project scope, and email notifications.
    </p>
    <div class="text-center py-5" style="color:#9ca3af;">
        <i class="bi bi-file-earmark-text fs-2 d-block mb-2"></i>
        Template management coming soon.
    </div>
</div>

@endsection
