<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectStatus extends Model
{
    protected $table = 'project_statuses';

    public $timestamps = false;

    protected $fillable = ['name'];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'status_id');
    }
}
