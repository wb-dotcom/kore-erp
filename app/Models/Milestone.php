<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Milestone extends Model
{
    protected $table = 'milestones';

    protected $fillable = ['deliverable_id', 'name', 'description', 'sort_order'];

    public function deliverable(): BelongsTo
    {
        return $this->belongsTo(Deliverable::class, 'deliverable_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'milestone_id')->orderBy('sort_order');
    }
}
