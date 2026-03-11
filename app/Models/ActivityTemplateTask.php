<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityTemplateTask extends Model
{
    protected $fillable = [
        'activity_template_activity_id',
        'name',
        'description',
        'relative_due_day',
        'assigned_role',
        'estimated_hours',
        'sort_order',
    ];

    protected $casts = [
        'estimated_hours' => 'float',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(ActivityTemplateActivity::class, 'activity_template_activity_id');
    }
}
