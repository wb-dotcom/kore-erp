<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $table = 'projects';

    protected $fillable = [
        'year',
        'project_number',
        'title',
        'company_id',
        'project_manager_id',
        'project_type_id',
        'status_id',
        'proposal_id',
        'program_id',
        'start_date',
        'end_date',
        'total_budget',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'total_budget' => 'decimal:2',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ProjectStatus::class, 'status_id');
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class, 'proposal_id');
    }

    /** The macro-level rollout this dealership site belongs to. */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(Deliverable::class, 'project_id')->orderBy('sort_order');
    }

    /** AIA architectural phases, ordered for display. */
    public function phases(): HasMany
    {
        return $this->hasMany(ProjectPhase::class, 'project_id')->orderBy('phase_order');
    }

    public function timesheetEntries(): HasMany
    {
        return $this->hasMany(TimesheetEntry::class, 'project_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ProjectNote::class, 'project_id')->orderBy('priority_order')->orderByDesc('created_at');
    }

    public function todos(): HasMany
    {
        return $this->hasMany(ProjectNote::class, 'project_id')->where('is_todo', true)->orderBy('is_done')->orderBy('due_date');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /** A project is billable when it has an approved linked proposal. */
    public function isBillable(): bool
    {
        return $this->proposal_id !== null;
    }

    /** Convenience: billing type inherited from the linked proposal. */
    public function getBillingTypeAttribute(): ?string
    {
        return $this->proposal?->billing_type;
    }
}
