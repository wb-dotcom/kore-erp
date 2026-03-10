<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One billable row in a proposal fee worksheet.
 *
 * Amount resolution (in priority order):
 *   1. amount_override — manual fixed amount (e.g. a subcontractor lump-sum)
 *   2. hours × rate   — computed
 *
 * @property int         $id
 * @property int         $proposal_id
 * @property string      $phase_code      SD | DD | CD | CA | custom
 * @property string|null $phase_label
 * @property string      $deliverable
 * @property string|null $milestone
 * @property string      $role_name
 * @property float       $hours
 * @property float       $rate
 * @property float       $amount
 * @property float|null  $amount_override
 * @property int         $sort_order
 */
class ProposalLineItem extends Model
{
    protected $fillable = [
        'proposal_id',
        'phase_code',
        'phase_label',
        'deliverable',
        'milestone',
        'role_name',
        'hours',
        'rate',
        'amount',
        'amount_override',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'hours'           => 'float',
        'rate'            => 'float',
        'amount'          => 'float',
        'amount_override' => 'float',
        'sort_order'      => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class, 'proposal_id');
    }

    // ── Computed ───────────────────────────────────────────────────────────────

    /**
     * The effective billable amount for this line item.
     * Uses the override if set, otherwise hours × rate.
     */
    public function getEffectiveAmountAttribute(): float
    {
        return $this->amount_override ?? $this->amount;
    }

    /**
     * Recompute and write the stored amount from current hours and rate.
     * Call this after updating hours or rate.
     */
    public function recalculate(): void
    {
        $this->amount = round($this->hours * $this->rate, 2);
        $this->save();
    }
}
