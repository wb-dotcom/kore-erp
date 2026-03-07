<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    public $timestamps = false;

    protected $table = 'holidays';

    protected $fillable = ['name', 'holiday_date', 'year'];

    protected $casts = [
        'holiday_date' => 'date',
    ];
}
