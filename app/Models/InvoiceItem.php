<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    public $timestamps = false;

    protected $table = 'invoice_items';

    protected $fillable = [
        'invoice_id',
        'deliverable_id',
        'user_id',
        'role_name',
        'hours',
        'rate',
        'timesheet_entry_ids',
        'description',
        'quantity',
        'unit_price',
        'line_total',
        'sort_order',
    ];

    protected $casts = [
        'quantity'            => 'decimal:2',
        'unit_price'          => 'decimal:2',
        'line_total'          => 'decimal:2',
        'hours'               => 'decimal:2',
        'rate'                => 'decimal:2',
        'timesheet_entry_ids' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function deliverable(): BelongsTo
    {
        return $this->belongsTo(Deliverable::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
