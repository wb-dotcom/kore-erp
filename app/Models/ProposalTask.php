<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalTask extends Model
{
    protected $fillable = [
        'proposal_activity_id',
        'proposal_deliverable_id',
        'name',
        'description',
        'relative_due_day',
        'assigned_role',
        'estimated_hours',
        'sort_order',
        'depends_on_task_id',
    ];

    protected $casts = [
        'estimated_hours' => 'float',
    ];

    /** Parent milestone (null when task is directly under a deliverable). */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(ProposalActivity::class, 'proposal_activity_id');
    }

    /** Parent deliverable (set when task is directly under a deliverable). */
    public function deliverable(): BelongsTo
    {
        return $this->belongsTo(ProposalDeliverable::class, 'proposal_deliverable_id');
    }

    /** The task this one depends on. */
    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(ProposalTask::class, 'depends_on_task_id');
    }
}
