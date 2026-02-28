<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    protected $table = 'contacts';

    protected $fillable = [
        'first_name',
        'last_name',
        'company_id',
        'contact_type_id',
        'email',
        'business_phone',
        'mobile_phone',
        'title',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function contactType(): BelongsTo
    {
        return $this->belongsTo(ContactType::class, 'contact_type_id');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
