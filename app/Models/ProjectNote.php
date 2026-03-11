<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectNote extends Model
{
    protected $table = 'project_notes';

    protected $fillable = [
        'project_id',
        'user_id',
        'content',
        'is_todo',
        'is_done',
        'due_date',
        'priority_order',
    ];

    protected $casts = [
        'is_todo'        => 'boolean',
        'is_done'        => 'boolean',
        'due_date'       => 'date',
        'priority_order' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isDueSoon(): bool
    {
        return $this->due_date && $this->due_date->lte(now()->addDays(3)) && ! $this->is_done;
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->lt(now()->startOfDay()) && ! $this->is_done;
    }
}
