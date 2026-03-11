<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingSchedulePeriod extends Model
{
    protected $fillable = [
        'billing_schedule_id',
        'period_start',
        'period_end',
        'fees_amount',
        'expenses_amount',
        'total_amount',
        'status',
        'invoice_id',
        'invoice_number',
        'is_locked',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'period_start'    => 'date',
        'period_end'      => 'date',
        'fees_amount'     => 'float',
        'expenses_amount' => 'float',
        'total_amount'    => 'float',
        'is_locked'       => 'boolean',
    ];

    public function billingSchedule(): BelongsTo
    {
        return $this->belongsTo(BillingSchedule::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft'    => 'Draft',
            'approved' => 'Approved',
            'invoiced' => 'Invoiced',
            'paid'     => 'Paid',
            'overdue'  => 'Overdue',
            default    => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft'    => 'secondary',
            'approved' => 'info',
            'invoiced' => 'primary',
            'paid'     => 'success',
            'overdue'  => 'danger',
            default    => 'secondary',
        };
    }
}
