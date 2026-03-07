<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEvent extends Model
{
    protected $table = 'calendar_events';

    protected $fillable = [
        'user_id',
        'title',
        'start_date',
        'end_date',
        'color',
        'all_day',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'all_day'    => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
