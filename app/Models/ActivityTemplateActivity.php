<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityTemplateActivity extends Model
{
    protected $fillable = [
        'activity_template_deliverable_id',
        'name',
        'description',
        'relative_start_day',
        'relative_end_day',
        'assigned_role',
        'budgeted_hours',
        'sort_order',
    ];

    protected $casts = [
        'budgeted_hours' => 'float',
    ];

    public function deliverable(): BelongsTo
    {
        return $this->belongsTo(ActivityTemplateDeliverable::class, 'activity_template_deliverable_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ActivityTemplateTask::class, 'activity_template_activity_id')
            ->orderBy('sort_order');
    }
}
