<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $table = 'tasks';

    protected $fillable = [
        'milestone_id',
        'deliverable_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
        'sort_order',
        'budget_hours',
        'rate',
    ];

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'budget_hours' => 'float',
        'rate'         => 'float',
    ];

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class, 'milestone_id');
    }

    public function deliverable(): BelongsTo
    {
        return $this->belongsTo(Deliverable::class, 'deliverable_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class, 'task_id')->with('user');
    }

    /** Dependencies: tasks that must complete before this task can start */
    public function dependencies(): HasMany
    {
        return $this->hasMany(TaskDependency::class, 'task_id')->with('dependsOn');
    }

    /** Dependants: tasks that depend on this task */
    public function dependants(): HasMany
    {
        return $this->hasMany(TaskDependency::class, 'depends_on_id');
    }

    /** Total hours assigned across all users */
    public function getAssignedHoursAttribute(): float
    {
        return (float) $this->assignments->sum('budget_hours');
    }

    /** Get the parent deliverable regardless of whether the task is direct or via milestone */
    public function getParentDeliverableAttribute(): ?Deliverable
    {
        if ($this->deliverable_id) {
            return $this->deliverable;
        }
        return $this->milestone?->deliverable;
    }
}
