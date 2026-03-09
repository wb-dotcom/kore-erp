<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'notes',
        'created_by',
    ];

    protected $casts = [
        'submitted_date' => 'date',
        'approved_date'  => 'date',
    ];

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

    public function getRefAttribute(): string
    {
        return "P{$this->year}-" . str_pad($this->proposal_number, 3, '0', STR_PAD_LEFT);
    }
}
