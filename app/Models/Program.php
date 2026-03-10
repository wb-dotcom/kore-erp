<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Program — macro-level rollout container.
 *
 * Example: "Ford Signature 2.0" is a Program. Each dealership site
 * that gets renovated under that program becomes a Project.
 *
 * @property int         $id
 * @property string      $code              e.g. "FORD-SIG-2"
 * @property string      $name              e.g. "Ford Signature 2.0"
 * @property int|null    $company_id
 * @property string|null $description
 * @property float       $global_budget     Ceiling budget across all sites
 * @property string|null $start_date
 * @property string|null $end_date
 * @property string      $status            active|on_hold|completed|cancelled
 * @property int|null    $created_by
 */
class Program extends Model
{
    protected $fillable = [
        'code',
        'name',
        'company_id',
        'description',
        'global_budget',
        'start_date',
        'end_date',
        'status',
        'created_by',
    ];

    protected $casts = [
        'global_budget' => 'decimal:2',
        'start_date'    => 'date',
        'end_date'      => 'date',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** All dealership-site projects under this rollout. */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'program_id');
    }

    /** All phases across every project in this program (for rollup reporting). */
    public function projectPhases(): HasManyThrough
    {
        return $this->hasManyThrough(ProjectPhase::class, Project::class);
    }

    // ── Computed / Aggregate Helpers ───────────────────────────────────────────

    /**
     * Sum of all phase fixed fees across every project in this program.
     * This is what was contracted, not the global_budget ceiling.
     */
    public function getTotalContractedFeeAttribute(): float
    {
        return $this->projectPhases()->sum('fixed_fee');
    }

    /**
     * Sum of all timesheet entry hours across every project in this program,
     * grouped lazily. Use the dedicated reporting query for dashboard performance.
     */
    public function getTotalLoggedHoursAttribute(): float
    {
        return $this->projects()
            ->withSum('timesheetEntries', 'hours')
            ->get()
            ->sum('timesheet_entries_sum_hours');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
