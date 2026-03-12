<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deliverable extends Model
{
    protected $table = 'deliverables';

    protected $fillable = [
        'project_id', 'name', 'description', 'sort_order',
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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class, 'deliverable_id')->orderBy('sort_order');
    }

    /** Tasks that belong directly to this deliverable (no milestone) */
    public function directTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'deliverable_id')
            ->whereNull('milestone_id')
            ->orderBy('sort_order');
    }

    // ── Computed aggregates (require milestones/tasks to be already loaded) ───

    /** Sum of all task budget_hours (direct + via milestones) */
    public function getComputedHoursAttribute(): float
    {
        $direct = $this->directTasks->sum('budget_hours');
        $viaMs  = $this->milestones->sum(fn ($m) => $m->tasks->sum('budget_hours'));
        return (float) ($direct + $viaMs);
    }

    /** Earliest start date from direct tasks and milestones */
    public function getComputedStartAttribute(): ?Carbon
    {
        $dates = collect();

        foreach ($this->directTasks as $t) {
            if ($t->start_date) $dates->push($t->start_date);
        }
        foreach ($this->milestones as $m) {
            $ms = $m->computed_start;
            if ($ms) $dates->push($ms);
        }
        if ($this->start_date) $dates->push($this->start_date);

        return $dates->isNotEmpty() ? $dates->min() : null;
    }

    /** Latest end date from direct tasks and milestones */
    public function getComputedEndAttribute(): ?Carbon
    {
        $dates = collect();

        foreach ($this->directTasks as $t) {
            if ($t->end_date) $dates->push($t->end_date);
        }
        foreach ($this->milestones as $m) {
            $me = $m->computed_end;
            if ($me) $dates->push($me);
        }
        if ($this->due_date) $dates->push($this->due_date);

        return $dates->isNotEmpty() ? $dates->max() : null;
    }
}
