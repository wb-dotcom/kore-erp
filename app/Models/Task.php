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

    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class, 'task_id');
    }
}
