<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-proposal billing rate override.
 *
 * Scope types and what scope_value contains:
 *   'role'      → role name string (e.g. "Principal", "Project Architect")
 *   'work_type' → work_type_id cast to string (e.g. "3")
 *   'phase'     → AIA phase code (e.g. "CA", "SD")
 *
 * Rate resolution in ProjectPhase::getActualLaborCostAttribute():
 *   1. Look for a matching proposal_rate_schedule for this engagement
 *   2. Fall back to schedule_of_fees global rate
 *   3. Fall back to user->hourly_cost if both are null
 */
class ProposalRateSchedule extends Model
{
    protected $table = 'proposal_rate_schedules';

    protected $fillable = [
        'proposal_id',
        'scope',
        'scope_value',
        'hourly_rate',
        'fixed_amount',
        'notes',
    ];

    protected $casts = [
        'hourly_rate'  => 'decimal:2',
        'fixed_amount' => 'decimal:2',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class, 'proposal_id');
    }

    /**
     * Resolve the effective hourly rate for a given user on a given proposal.
     * Checks role-scoped rates first, then falls back to the global schedule.
     */
    public static function resolveRateForUser(User $user, Proposal $proposal): float
    {
        // 1. Check proposal-specific rate by role name
        $rate = static::where('proposal_id', $proposal->id)
            ->where('scope', 'role')
            ->where('scope_value', $user->role?->name ?? '')
            ->value('hourly_rate');

        if ($rate !== null) {
            return (float) $rate;
        }

        // 2. Fall back to global schedule of fees
        return ScheduleOfFee::rateForRole($user->role?->name ?? '');
    }
}
