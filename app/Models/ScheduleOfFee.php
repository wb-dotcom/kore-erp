<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Maps a role name to its billing/cost rate.
 * The schedule_of_fees table already exists in the SQL installer.
 *
 * This model is used by ProjectPhase::getActualLaborCostAttribute()
 * to look up the rate for a given user's role.
 */
class ScheduleOfFee extends Model
{
    protected $table = 'schedule_of_fees';

    public $timestamps = false;

    protected $fillable = [
        'fee_schedule_id',
        'role_name',
        'hourly_rate',
        'project_type_id',
        'effective_date',
    ];

    protected $casts = [
        'hourly_rate'    => 'decimal:2',
        'effective_date' => 'date',
    ];

    public function feeSchedule(): BelongsTo
    {
        return $this->belongsTo(FeeSchedule::class, 'fee_schedule_id');
    }

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }

    /**
     * Look up the current billing rate for a given role name.
     * Returns the most recent rate that is on or before today.
     */
    public static function rateForRole(string $roleName): float
    {
        return (float) static::where('role_name', $roleName)
            ->where(function ($q) {
                $q->whereNull('effective_date')
                  ->orWhere('effective_date', '<=', now()->toDateString());
            })
            ->orderByDesc('effective_date')
            ->value('hourly_rate') ?? 0;
    }
}
