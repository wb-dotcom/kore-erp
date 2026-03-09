<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Approval extends Model
{
    public $timestamps = false;

    protected $table = 'approvals';

    protected $fillable = [
        'approval_type',
        'reference_id',
        'approver_id',
        'status',
        'comments',
        'actioned_at',
        'created_at',
    ];

    protected $casts = [
        'actioned_at' => 'datetime',
        'created_at'  => 'datetime',
    ];

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
