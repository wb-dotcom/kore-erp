<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'reference_id',
        'details',
        'ip_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function record(string $action, string $module = null, int $referenceId = null, string $details = null): void
    {
        static::create([
            'user_id'      => auth()->id(),
            'action'       => $action,
            'module'       => $module,
            'reference_id' => $referenceId,
            'details'      => $details,
            'ip_address'   => request()->ip(),
        ]);
    }
}
