<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProposalDeliverable extends Model
{
    protected $fillable = [
        'proposal_id',
        'name',
        'description',
        'sort_order',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ProposalActivity::class)->orderBy('sort_order');
    }

    public function getTotalBudgetedHoursAttribute(): float
    {
        return (float) $this->activities()->sum('budgeted_hours');
    }
}
