<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CandidateSourceMaster extends Model
{
    protected $table = 'candidate_source_master';

    protected $fillable = [
        'source_name',
        'description'
    ];

    public function candidates(): HasMany
    {
        return $this->hasMany(CandidateMaster::class, 'source_id');
    }
} 