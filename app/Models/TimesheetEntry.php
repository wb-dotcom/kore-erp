<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimesheetEntry extends Model
{
    protected $table = 'timesheet_entries';

    protected $fillable = [
        'timesheet_id',
        'project_id',
        'deliverable_id',
        'milestone_id',
        'task_id',
        'entry_date',
        'hours',
        'entry_type',
        'notes',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'hours'      => 'decimal:2',
    ];

    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(Timesheet::class, 'timesheet_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
