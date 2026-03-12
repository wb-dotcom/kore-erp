<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeSchedule extends Model
{
    protected $table = 'fee_schedules';

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_default' => 'boolean',
    ];

    public function rates(): HasMany
    {
        return $this->hasMany(ScheduleOfFee::class, 'fee_schedule_id')->orderBy('role_name');
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class, 'fee_schedule_id');
    }

    /** Get rate for a role name in this schedule. */
    public function rateForRole(string $roleName): float
    {
        return (float) $this->rates()
            ->where('role_name', $roleName)
            ->value('hourly_rate') ?? 0;
    }

    /** Return the default schedule, or null if none set. */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->where('is_active', true)->first();
    }
}
