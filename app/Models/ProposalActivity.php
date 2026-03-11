<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProposalActivity extends Model
{
    protected $fillable = [
        'proposal_deliverable_id',
        'name',
        'description',
        'relative_start_day',
        'relative_end_day',
        'assigned_role',
        'budgeted_hours',
        'sort_order',
    ];

    protected $casts = [
        'budgeted_hours' => 'float',
    ];

    public function deliverable(): BelongsTo
    {
        return $this->belongsTo(ProposalDeliverable::class, 'proposal_deliverable_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProposalTask::class)->orderBy('sort_order');
    }

    public function getTotalEstimatedHoursAttribute(): float
    {
        return (float) $this->tasks()->sum('estimated_hours');
    }
}
