<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ProjectPhase — an AIA architectural stage on a specific project.
 *
 * Standard AIA codes (used as convention, not enforced by DB):
 *   PD  – Pre-Design / Programming
 *   SD  – Schematic Design
 *   DD  – Design Development
 *   CD  – Construction Documents
 *   BID – Bidding & Negotiation
 *   CA  – Construction Administration
 *   PO  – Post-Occupancy
 *
 * Budget Variance is calculated as:
 *   fixed_fee − SUM(timesheet_entries.hours × user_billing_rate)
 *
 * @property int         $id
 * @property int         $project_id
 * @property string      $name                  e.g. "Schematic Design"
 * @property string|null $code                  e.g. "SD"
 * @property int         $phase_order
 * @property float       $fixed_fee             Contracted fee for this phase
 * @property float       $fee_percentage        % of project total budget
 * @property float       $percent_complete      0–100
 * @property float       $estimated_hours       Labor hours budgeted
 * @property string      $status                not_started|in_progress|on_hold|completed
 * @property string|null $planned_start_date
 * @property string|null $planned_end_date
 * @property string|null $actual_start_date
 * @property string|null $actual_end_date
 * @property string|null $notes
 */
class ProjectPhase extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'code',
        'phase_order',
        'fixed_fee',
        'fee_percentage',
        'percent_complete',
        'estimated_hours',
        'status',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
        'notes',
    ];

    protected $casts = [
        'fixed_fee'          => 'decimal:2',
        'fee_percentage'     => 'decimal:2',
        'percent_complete'   => 'decimal:2',
        'estimated_hours'    => 'decimal:2',
        'planned_start_date' => 'date',
        'planned_end_date'   => 'date',
        'actual_start_date'  => 'date',
        'actual_end_date'    => 'date',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * All timesheet entries logged against this phase.
     * This is the source of truth for actual labor burn.
     */
    public function timesheetEntries(): HasMany
    {
        return $this->hasMany(TimesheetEntry::class, 'project_phase_id');
    }

    // ── Computed Helpers ───────────────────────────────────────────────────────

    /**
     * Total hours logged against this phase from all timesheet entries.
     */
    public function getActualHoursAttribute(): float
    {
        return (float) $this->timesheetEntries()->sum('hours');
    }

    /**
     * Actual labor cost for this phase.
     *
     * Joins through the timesheet → user → schedule_of_fees chain.
     * Uses the user's hourly_cost if set, otherwise falls back to the
     * most recent schedule_of_fees rate for that user's role.
     *
     * NOTE: This is a PHP-level calculation suitable for single-phase views.
     * Use the raw SQL aggregate in the reporting engine (Phase 4) for bulk queries.
     */
    public function getActualLaborCostAttribute(): float
    {
        return $this->timesheetEntries()
            ->with('timesheet.user.role')
            ->get()
            ->sum(function (TimesheetEntry $entry): float {
                $user = $entry->timesheet?->user;
                if (! $user) {
                    return 0;
                }

                $rate = $user->hourly_cost
                    ?? ScheduleOfFee::where('role_name', $user->role?->name)
                        ->where(function ($q) {
                            $q->whereNull('effective_date')
                              ->orWhere('effective_date', '<=', now()->toDateString());
                        })
                        ->orderByDesc('effective_date')
                        ->value('hourly_rate')
                    ?? 0;

                return (float) $entry->hours * (float) $rate;
            });
    }

    /**
     * Budget Variance: positive = under budget, negative = over budget.
     */
    public function getBudgetVarianceAttribute(): float
    {
        return (float) $this->fixed_fee - $this->actual_labor_cost;
    }

    /**
     * Remaining hours before estimated budget is exhausted.
     */
    public function getRemainingHoursAttribute(): float
    {
        return (float) $this->estimated_hours - $this->actual_hours;
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('phase_order');
    }
}
