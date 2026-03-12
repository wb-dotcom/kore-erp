<div class="row g-2">
    <div class="col-sm-{{ $isHourly ? '4' : '6' }}">
        <input type="text" name="name" class="form-control form-control-sm" placeholder="Task name" required>
    </div>
    <div class="col-sm-2">
        <input type="date" name="start_date" class="form-control form-control-sm" title="Start date">
    </div>
    <div class="col-sm-2">
        <input type="date" name="end_date" class="form-control form-control-sm" title="Due date">
    </div>
    @if($isHourly)
    <div class="col-sm-2">
        <input type="number" name="budget_hours" class="form-control form-control-sm" step="0.25" min="0" placeholder="Hrs">
    </div>
    @endif
    <div class="col-sm-{{ $isHourly ? '1' : '2' }}">
        <button type="submit" class="btn btn-sm btn-primary w-100">Add</button>
    </div>
</div>
