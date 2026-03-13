<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'work_type_id',
        'project_manager_id',
        'total_budgeted_hours',
        'created_by',
        'billing_type',
        'billing_cycle',
    ];

    protected $casts = [
        'total_budgeted_hours' => 'float',
    ];

    /** Billing type → label map */
    public const BILLING_TYPES = [
        'fixed'            => 'Fixed Fee',
        'time_and_material' => 'Time & Material',
        'hybrid'           => 'Hybrid',
        'retainer'         => 'Retainer',
        'per_deliverable'  => 'Per Deliverable',
    ];

    /**
     * Which billing cycles are valid/recommended for each billing type.
     * Used to filter the billing_cycle dropdown in the UI.
     */
    public const BILLING_TYPE_CYCLES = [
        'fixed'            => ['on_completion', 'custom'],
        'time_and_material' => ['biweekly', 'monthly', 'quarterly', 'custom'],
        'hybrid'           => ['monthly', 'quarterly', 'on_completion', 'custom'],
        'retainer'         => ['biweekly', 'monthly', 'quarterly'],
        'per_deliverable'  => ['on_completion', 'custom'],
    ];

    public const BILLING_CYCLES = [
        'biweekly'      => 'Bi-Weekly',
        'monthly'       => 'Monthly',
        'quarterly'     => 'Quarterly',
        'on_completion' => 'On Completion',
        'custom'        => 'Custom',
    ];

    /**
     * Fields shown per billing type.
     *  hours    = budgeted/estimated hours on activities & tasks
     *  rate     = hourly rate override field (T&M / hybrid only)
     *  del_fee  = fixed deliverable fee (per_deliverable / hybrid)
     */
    public const BILLING_TYPE_FIELDS = [
        'fixed'            => ['hours'],
        'time_and_material' => ['hours', 'rate'],
        'hybrid'           => ['hours', 'rate', 'del_fee'],
        'retainer'         => ['hours'],
        'per_deliverable'  => ['hours', 'del_fee'],
    ];

    public function getBillingTypeLabelAttribute(): string
    {
        return self::BILLING_TYPES[$this->billing_type] ?? 'Any';
    }

    public function getBillingCycleLabelAttribute(): string
    {
        return self::BILLING_CYCLES[$this->billing_cycle] ?? '—';
    }

    /** Returns which field groups are active for this template's billing type. */
    public function getActiveFieldsAttribute(): array
    {
        return self::BILLING_TYPE_FIELDS[$this->billing_type] ?? ['hours'];
    }

    public function workType(): BelongsTo
    {
        return $this->belongsTo(WorkType::class);
    }

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(ActivityTemplateDeliverable::class)->orderBy('sort_order');
    }

    /** Recalculate and persist total_budgeted_hours from nested activities and direct tasks. */
    public function recalculateHours(): void
    {
        $total = $this->deliverables()
            ->with('activities', 'directTasks')
            ->get()
            ->sum(function ($d) {
                $activityHours  = $d->activities->sum('budgeted_hours');
                $directTaskHours = $d->directTasks->sum('estimated_hours');
                return $activityHours + $directTaskHours;
            });

        $this->update(['total_budgeted_hours' => $total]);
    }
}
