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
}
