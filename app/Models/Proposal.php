<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Proposal extends Model
{
    protected $table = 'proposals';

    protected $fillable = [
        'year',
        'proposal_number',
        'title',
        'company_id',
        'sector_id',
        'work_type_id',
        'account_manager_id',
        'status_id',
        'po_number',
        'description',
        'submitted_date',
        'approved_date',
        // Phase 1: billing architecture
        'billing_type',
        'billing_cycle',
        'payment_terms_days',
        'notes',
        'created_by',
        // Phase 1.5: proposal builder
        'executive_summary',
        'scope_of_work',
        'terms_and_conditions',
        'total_fee',
    ];

    protected $casts = [
        'submitted_date'     => 'date',
        'approved_date'      => 'date',
        'payment_terms_days' => 'integer',
        'total_fee'          => 'float',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class, 'sector_id');
    }

    public function workType(): BelongsTo
    {
        return $this->belongsTo(WorkType::class, 'work_type_id');
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ProposalStatus::class, 'status_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class, 'proposal_id');
    }

    /**
     * Per-engagement billing rate overrides.
     * These take priority over the global schedule_of_fees for burn calculations.
     */
    public function rateSchedules(): HasMany
    {
        return $this->hasMany(ProposalRateSchedule::class, 'proposal_id');
    }

    /** Phase 1.5: fee worksheet line items. */
    public function lineItems(): HasMany
    {
        return $this->hasMany(ProposalLineItem::class, 'proposal_id')->orderBy('sort_order');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function getRefAttribute(): string
    {
        return 'P' . $this->year . '-' . str_pad($this->proposal_number, 3, '0', STR_PAD_LEFT);
    }

    /** True if client is billed based on time logged (not a fixed fee). */
    public function isTimeAndMaterial(): bool
    {
        return in_array($this->billing_type, ['time_and_material', 'hybrid']);
    }

    /** True if fixed lump-sum regardless of hours spent. */
    public function isFixedFee(): bool
    {
        return $this->billing_type === 'fixed';
    }
}
