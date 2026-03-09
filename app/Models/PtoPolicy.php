<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PtoPolicy extends Model
{
    protected $table = 'pto_policies';

    protected $fillable = [
        'user_id',
        'annual_pto_hours',
        'carry_over_hours',
        'effective_date',
    ];

    protected $casts = [
        'annual_pto_hours' => 'decimal:2',
        'carry_over_hours' => 'decimal:2',
        'effective_date'   => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
