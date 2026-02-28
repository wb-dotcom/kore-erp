<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $table = 'regions';

    public $timestamps = false;

    protected $fillable = ['name'];

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'region_id');
    }
}
