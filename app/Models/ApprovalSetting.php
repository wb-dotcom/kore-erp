<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalSetting extends Model
{
    public $timestamps = false;

    protected $table = 'approval_settings';

    protected $fillable = [
        'approval_type',
        'approver_user_id',
        'department',
        'level',
        'created_at',
    ];

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
}
