<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * KORE ERP - User Model
 *
 * @property int    $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $password
 * @property int    $role_id
 * @property string $avatar
 * @property string $phone
 * @property string $department
 * @property string $hire_date
 * @property bool   $is_active
 * @property string $remember_token
 * @property string $last_login
 */
class User extends Authenticatable
{
    protected $table = 'users';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role_id',
        'avatar',
        'phone',
        'department',
        'hire_date',
        'is_active',
        'last_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'hire_date'  => 'date',
        'last_login' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class, 'user_id');
    }

    public function taskAssignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class, 'user_id');
    }

    public function timeOffRequests(): HasMany
    {
        return $this->hasMany(TimeOffRequest::class, 'user_id');
    }

    public function pto(): HasOne
    {
        return $this->hasOne(PtoPolicy::class, 'user_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }

    // ─── Accessors ────────────────────────────────────────────────

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get initials for avatar
     */
    public function getInitialsAttribute(): string
    {
        return strtoupper(
            substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1)
        );
    }

    // ─── Helpers ──────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role?->name === 'Admin';
    }

    public function isManager(): bool
    {
        return in_array($this->role?->name, ['Admin', 'Manager']);
    }

    public function hasRole(string $role): bool
    {
        return $this->role?->name === $role;
    }
}
