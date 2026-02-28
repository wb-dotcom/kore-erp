<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimesheetPeriod extends Model
{
    protected $table = 'timesheet_periods';

    public $timestamps = false;

    protected $fillable = ['start_date', 'end_date', 'due_date'];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'due_date'   => 'date',
    ];

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class, 'period_id');
    }

    public function getLabelAttribute(): string
    {
        return $this->start_date->format('M d') . ' – ' . $this->end_date->format('M d, Y');
    }
}
