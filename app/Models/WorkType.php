<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkType extends Model
{
    protected $table = 'work_types';

    public $timestamps = false;

    protected $fillable = ['name'];

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class, 'work_type_id');
    }
}
