<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sector extends Model
{
    protected $table = 'sectors';

    public $timestamps = false;

    protected $fillable = ['name'];

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'sector_id');
    }
}
