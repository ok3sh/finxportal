<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CandidateSkillMaster extends Model
{
    protected $table = 'candidate_skill_master';

    protected $fillable = [
        'skill_name'
    ];

    public function candidates(): BelongsToMany
    {
        return $this->belongsToMany(CandidateMaster::class, 'candidate_skills', 'skill_id', 'candidate_id');
    }
} 