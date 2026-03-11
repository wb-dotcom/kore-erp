<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingSchedule extends Model
{
    protected $fillable = [
        'proposal_id',
        'billing_type',
        'billing_cycle',
        'start_date',
        'end_date',
        'include_expenses',
        'payment_terms_days',
        'notes',
    ];

    protected $casts = [
        'start_date'       => 'date',
        'end_date'         => 'date',
        'include_expenses' => 'boolean',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function periods(): HasMany
    {
        return $this->hasMany(BillingSchedulePeriod::class)->orderBy('sort_order')->orderBy('period_start');
    }

    // ── Computed Helpers ────────────────────────────────────────────────────────

    public function getTotalInvoicedAttribute(): float
    {
        return (float) $this->periods()->whereIn('status', ['invoiced', 'paid'])->sum('total_amount');
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->periods()->where('status', 'paid')->sum('total_amount');
    }

    public function getTotalPendingAttribute(): float
    {
        return (float) $this->periods()->whereNotIn('status', ['invoiced', 'paid'])->sum('total_amount');
    }
}
