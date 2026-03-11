<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkScheduleItem extends Model
{
    protected $table = 'work_schedule_items';

    protected $fillable = [
        'task_assignment_id',
        'user_id',
        'week_start',
        'scheduled_hours',
        'priority_order',
        'status',
        'notes',
    ];

    protected $casts = [
        'week_start'       => 'date',
        'scheduled_hours'  => 'decimal:2',
        'priority_order'   => 'integer',
    ];

    public function taskAssignment(): BelongsTo
    {
        return $this->belongsTo(TaskAssignment::class, 'task_assignment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Convenience accessor to the underlying task. */
    public function getTaskAttribute()
    {
        return $this->taskAssignment?->task;
    }
}
