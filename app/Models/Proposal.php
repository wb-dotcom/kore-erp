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
        'contact_id',
        'sector_id',
        'work_type_id',
        'project_type_id',
        'account_manager_id',
        'status_id',
        'po_number',
        'vendor_code',
        'description',
        'submitted_date',
        'approved_date',
        'expiry_date',
        // Billing architecture
        'billing_type',
        'billing_cycle',
        'payment_terms_days',
        'notes',
        'created_by',
        'program_id',
        // Proposal content
        'executive_summary',
        'scope_of_work',
        'terms_and_conditions',
        // Financials
        'total_fee',
        'contract_value',
        'expenses_reserve',
        // Google Doc integration
        'google_doc_url',
        'google_doc_synced_at',
    ];

    protected $casts = [
        'submitted_date'       => 'date',
        'approved_date'        => 'date',
        'expiry_date'          => 'date',
        'payment_terms_days'   => 'integer',
        'total_fee'            => 'float',
        'contract_value'       => 'float',
        'expenses_reserve'     => 'float',
        'google_doc_synced_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class, 'sector_id');
    }

    public function workType(): BelongsTo
    {
        return $this->belongsTo(WorkType::class, 'work_type_id');
    }

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
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

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
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

    /** Fee worksheet line items. */
    public function lineItems(): HasMany
    {
        return $this->hasMany(ProposalLineItem::class, 'proposal_id')->orderBy('sort_order');
    }

    /** Billing schedule with auto-generated invoice periods. */
    public function billingSchedule(): HasOne
    {
        return $this->hasOne(BillingSchedule::class, 'proposal_id');
    }

    /** Proposal deliverables (top-level work breakdown). */
    public function deliverables(): HasMany
    {
        return $this->hasMany(ProposalDeliverable::class, 'proposal_id')->orderBy('sort_order');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function getRefAttribute(): string
    {
        return 'P' . $this->year . '-' . str_pad($this->proposal_number, 3, '0', STR_PAD_LEFT);
    }

    /** Net professional fees = contract value minus expenses reserve. */
    public function getNetFeesAttribute(): float
    {
        return (float) (($this->contract_value ?? $this->total_fee ?? 0) - ($this->expenses_reserve ?? 0));
    }

    public function isApproved(): bool
    {
        return strtolower($this->status?->name ?? '') === 'approved';
    }

    public function isTimeAndMaterial(): bool
    {
        return in_array($this->billing_type, ['time_and_material', 'hybrid']);
    }

    public function isFixedFee(): bool
    {
        return $this->billing_type === 'fixed';
    }
}
