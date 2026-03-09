<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProposalStatus extends Model
{
    protected $table = 'proposal_statuses';

    public $timestamps = false;

    protected $fillable = ['name'];

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class, 'status_id');
    }
}
