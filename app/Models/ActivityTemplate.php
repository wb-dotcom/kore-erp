<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'work_type_id',
        'project_manager_id',
        'total_budgeted_hours',
        'created_by',
    ];

    protected $casts = [
        'total_budgeted_hours' => 'float',
    ];

    public function workType(): BelongsTo
    {
        return $this->belongsTo(WorkType::class);
    }

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(ActivityTemplateDeliverable::class)->orderBy('sort_order');
    }

    /** Recalculate and persist total_budgeted_hours from nested activities. */
    public function recalculateHours(): void
    {
        $total = $this->deliverables()
            ->with('activities')
            ->get()
            ->sum(fn ($d) => $d->activities->sum('budgeted_hours'));

        $this->update(['total_budgeted_hours' => $total]);
    }
}
