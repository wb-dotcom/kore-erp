<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeOffRequest extends Model
{
    protected $table = 'time_off_requests';

    protected $fillable = [
        'user_id',
        'request_type',
        'start_date',
        'end_date',
        'hours',
        'reason',
        'status',
        'approved_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'hours'      => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
