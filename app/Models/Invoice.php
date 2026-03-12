<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $table = 'invoices';

    protected $fillable = [
        'invoice_number',
        'proposal_id',
        'billing_schedule_period_id',
        'project_id',
        'company_id',
        'invoice_date',
        'due_date',
        'status',
        'invoice_type',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'paid_amount',
        'balance_due',
        'notes',
        'sent_at',
        'paid_at',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date'     => 'date',
        'sent_at'      => 'datetime',
        'paid_at'      => 'datetime',
        'subtotal'     => 'decimal:2',
        'tax_rate'     => 'decimal:2',
        'tax_amount'   => 'decimal:2',
        'total'        => 'decimal:2',
        'paid_amount'  => 'decimal:2',
        'balance_due'  => 'decimal:2',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function billingSchedulePeriod(): BelongsTo
    {
        return $this->belongsTo(BillingSchedulePeriod::class, 'billing_schedule_period_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class)->orderBy('payment_date');
    }

    // ── Business Logic ─────────────────────────────────────────────────────────

    public function recalculate(): void
    {
        $subtotal = $this->items()->sum('line_total');
        $taxAmt   = round($subtotal * ($this->tax_rate / 100), 2);
        $total    = $subtotal + $taxAmt;
        $paid     = $this->payments()->sum('amount');
        $balance  = max(0, $total - $paid);

        $this->update([
            'subtotal'    => $subtotal,
            'tax_amount'  => $taxAmt,
            'total'       => $total,
            'paid_amount' => $paid,
            'balance_due' => $balance,
        ]);
    }

    public function recalculateBalance(): void
    {
        $paid    = $this->payments()->sum('amount');
        $balance = max(0, (float) $this->total - $paid);
        $status  = $this->status;

        if ($balance <= 0 && (float) $this->total > 0) {
            $status = 'paid';
        } elseif ($paid > 0 && $balance > 0 && !in_array($status, ['void'])) {
            $status = 'partial';
        }

        $this->update([
            'paid_amount' => $paid,
            'balance_due' => $balance,
            'status'      => $status,
            'paid_at'     => ($status === 'paid') ? ($this->paid_at ?? now()) : $this->paid_at,
        ]);
    }

    public function isOverdue(): bool
    {
        return !in_array($this->status, ['paid', 'void']) && $this->due_date && $this->due_date->isPast();
    }

    public function getBalanceAttribute(): float
    {
        return max(0, (float) $this->total - (float) $this->paid_amount);
    }

    public function getInvoiceTypeLabelAttribute(): string
    {
        return match ($this->invoice_type) {
            'fixed_auto' => 'Fixed Fee',
            'tm_auto'    => 'Time & Material',
            'retainer'   => 'Retainer',
            'manual'     => 'Manual',
            default      => ucfirst(str_replace('_', ' ', $this->invoice_type ?? 'Manual')),
        };
    }

    public static function nextNumber(): string
    {
        $prefix = 'INV-';
        $last   = static::where('invoice_number', 'like', $prefix . '%')
                        ->orderByDesc('invoice_number')->value('invoice_number');
        $num    = $last ? ((int) str_replace($prefix, '', $last)) + 1 : 1;
        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
