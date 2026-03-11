@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0 fw-700" style="font-size:1rem;">{{ $title }}</h4>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

{{-- Lookup nav --}}
<div class="d-flex gap-2 mb-4" style="flex-wrap:wrap;">
    @foreach([
        'sectors'          => 'Sectors',
        'regions'          => 'Regions',
        'work-types'       => 'Work Types',
        'contact-types'    => 'Contact Types',
        'project-types'    => 'Project Types',
        'project-statuses' => 'Project Statuses',
    ] as $t => $label)
    <a href="{{ route('admin.'.$t.'.index') }}"
        class="btn btn-sm {{ $type === $t ? 'btn-primary' : 'btn-outline-secondary' }}">
        {{ $label }}
    </a>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="kore-card p-0">
            <table class="table kore-table mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        @if($hasIsBillable ?? false)<th class="text-center">Billable</th>@endif
                        <th class="text-center">Used</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                    <tr>
                        <td class="fw-500" style="font-size:0.82rem;">{{ $item->name }}</td>
                        @if($hasIsBillable ?? false)
                        <td class="text-center">
                            @if($item->is_billable)
                                <span class="badge" style="background:#dcfce7; color:#15803d; font-size:0.7rem;">Yes</span>
                            @else
                                <span class="badge" style="background:#f3f4f6; color:#6b7280; font-size:0.7rem;">No</span>
                            @endif
                        </td>
                        @endif
                        <td class="text-center" style="font-size:0.8rem; color:#6b7280;">
                            {{ $item->companies_count ?? $item->proposals_count ?? $item->contacts_count ?? $item->projects_count ?? 0 }}
                        </td>
                        <td class="text-end">
                            @php $routeBase = 'admin.'.$type; @endphp
                            <form action="{{ route($routeBase.'.destroy', $item) }}" method="POST"
                                onsubmit="return confirm('Delete {{ $item->name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light text-danger">
                                    <i class="bi bi-trash" style="font-size:0.75rem;"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ ($hasIsBillable ?? false) ? 4 : 3 }}" class="text-center py-4" style="color:#9ca3af; font-size:0.8rem;">
                            No {{ strtolower($title) }} yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="kore-card">
            <div class="fw-600 mb-3" style="font-size:0.85rem;">Add {{ \Illuminate\Support\Str::singular($title) }}</div>
            <form action="{{ route('admin.'.$type.'.store') }}" method="POST">
                @csrf
                @if(session('error'))
                <div class="alert alert-danger py-2 mb-2" style="font-size:0.78rem;">{{ session('error') }}</div>
                @endif
                <div class="mb-3">
                    <label class="form-label" style="font-size:0.78rem;">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control form-control-sm" required>
                </div>
                @if($hasIsBillable ?? false)
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_billable" value="1" id="isBillableCheck" checked>
                        <label class="form-check-label" for="isBillableCheck" style="font-size:0.78rem;">Billable type</label>
                    </div>
                </div>
                @endif
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-plus-lg me-1"></i> Add
                </button>
            </form>
        </div>
    </div>
</div>

@endsection
