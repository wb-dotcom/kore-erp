<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProposalDeliverable extends Model
{
    protected $fillable = [
        'proposal_id',
        'name',
        'description',
        'sort_order',
        'max_hours',
        'depends_on_deliverable_id',
    ];

    protected $casts = [
        'max_hours' => 'float',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ProposalActivity::class)->orderBy('sort_order');
    }

    /** Tasks directly under this deliverable (no milestone). */
    public function directTasks(): HasMany
    {
        return $this->hasMany(ProposalTask::class, 'proposal_deliverable_id')
            ->whereNull('proposal_activity_id')
            ->orderBy('sort_order');
    }

    /** The deliverable this one must wait for. */
    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(ProposalDeliverable::class, 'depends_on_deliverable_id');
    }

    public function getTotalBudgetedHoursAttribute(): float
    {
        $actHours  = (float) $this->activities()->sum('budgeted_hours');
        $taskHours = (float) $this->directTasks()->sum('estimated_hours');
        return $actHours + $taskHours;
    }
}
