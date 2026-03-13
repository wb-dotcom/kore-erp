<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityTemplateTask extends Model
{
    protected $fillable = [
        'activity_template_activity_id',
        'activity_template_deliverable_id',
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
        return $this->belongsTo(ActivityTemplateActivity::class, 'activity_template_activity_id');
    }

    /** Parent deliverable (set when task is directly under a deliverable, no milestone). */
    public function deliverable(): BelongsTo
    {
        return $this->belongsTo(ActivityTemplateDeliverable::class, 'activity_template_deliverable_id');
    }

    /** The task this one depends on (must complete before this one starts). */
    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(ActivityTemplateTask::class, 'depends_on_task_id');
    }
}
