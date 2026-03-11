<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalTask extends Model
{
    protected $fillable = [
        'proposal_activity_id',
        'name',
        'description',
        'relative_due_day',
        'assigned_role',
        'estimated_hours',
        'sort_order',
    ];

    protected $casts = [
        'estimated_hours' => 'float',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(ProposalActivity::class, 'proposal_activity_id');
    }
}
