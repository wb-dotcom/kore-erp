<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAssignment extends Model
{
    protected $table = 'task_assignments';

    protected $fillable = [
        'task_id',
        'user_id',
        'role',
        'budget_hours',
        'actual_hours',
        'notes',
    ];

    protected $casts = [
        'budget_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
