<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Timesheet extends Model
{
    protected $table = 'timesheets';

    protected $fillable = [
        'user_id',
        'period_id',
        'status',
        'submitted_at',
        'total_hours',
        'notes',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'total_hours'  => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(TimesheetPeriod::class, 'period_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(TimesheetEntry::class, 'timesheet_id');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }
}
