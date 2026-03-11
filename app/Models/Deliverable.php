<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deliverable extends Model
{
    protected $table = 'deliverables';

    protected $fillable = [
        'project_id', 'name', 'description', 'sort_order',
        'budget_hours', 'rate', 'deliverable_fee', 'billing_status', 'due_date',
    ];

    protected $casts = [
        'budget_hours'    => 'float',
        'rate'            => 'float',
        'deliverable_fee' => 'float',
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
}
