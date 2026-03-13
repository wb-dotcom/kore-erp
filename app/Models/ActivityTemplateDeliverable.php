<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityTemplateDeliverable extends Model
{
    protected $fillable = [
        'activity_template_id',
        'name',
        'description',
        'sort_order',
        'max_hours',
        'depends_on_deliverable_id',
    ];

    protected $casts = [
        'max_hours' => 'float',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ActivityTemplate::class, 'activity_template_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ActivityTemplateActivity::class, 'activity_template_deliverable_id')
            ->orderBy('sort_order');
    }

    /** Tasks that live directly under this deliverable (no milestone grouping). */
    public function directTasks(): HasMany
    {
        return $this->hasMany(ActivityTemplateTask::class, 'activity_template_deliverable_id')
            ->whereNull('activity_template_activity_id')
            ->orderBy('sort_order');
    }

    /** The deliverable this one depends on (must complete before this one starts). */
    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(ActivityTemplateDeliverable::class, 'depends_on_deliverable_id');
    }

    /** Total hours across all milestones + direct tasks. */
    public function getTotalHoursAttribute(): float
    {
        $actHours  = $this->activities->sum('budgeted_hours');
        $taskHours = $this->directTasks->sum('estimated_hours');
        return $actHours + $taskHours;
    }
}
