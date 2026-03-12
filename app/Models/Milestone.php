<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Milestone extends Model
{
    protected $table = 'milestones';

    protected $fillable = [
        'deliverable_id', 'name', 'description', 'sort_order',
        'budget_hours', 'rate', 'deliverable_fee', 'billing_status',
        'start_date', 'due_date',
    ];

    protected $casts = [
        'budget_hours'    => 'float',
        'rate'            => 'float',
        'deliverable_fee' => 'float',
        'start_date'      => 'date',
        'due_date'        => 'date',
    ];

    public function deliverable(): BelongsTo
    {
        return $this->belongsTo(Deliverable::class, 'deliverable_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'milestone_id')->orderBy('sort_order');
    }

    // ── Computed aggregates ───────────────────────────────────────────────────

    /** Sum of all tasks' budget_hours */
    public function getComputedHoursAttribute(): float
    {
        return (float) $this->tasks->sum('budget_hours');
    }

    /** Earliest start date from tasks */
    public function getComputedStartAttribute(): ?Carbon
    {
        $dates = $this->tasks->filter(fn ($t) => $t->start_date)->pluck('start_date');
        if ($this->start_date) $dates->push($this->start_date);
        return $dates->isNotEmpty() ? $dates->min() : null;
    }

    /** Latest end date from tasks */
    public function getComputedEndAttribute(): ?Carbon
    {
        $dates = $this->tasks->filter(fn ($t) => $t->end_date)->pluck('end_date');
        if ($this->due_date) $dates->push($this->due_date);
        return $dates->isNotEmpty() ? $dates->max() : null;
    }
}
