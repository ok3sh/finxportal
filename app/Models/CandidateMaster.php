<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CandidateMaster extends Model
{
    protected $table = 'candidates_master';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'source_id',
        'resume_path',
        'notes',
        'current_status'
    ];

    // Relationships
    public function source(): BelongsTo
    {
        return $this->belongsTo(CandidateSourceMaster::class, 'source_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(CandidateSkillMaster::class, 'candidate_skills', 'candidate_id', 'skill_id');
    }

    public function candidateJobs(): HasMany
    {
        return $this->hasMany(CandidateJob::class, 'candidate_id');
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class, 'candidate_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class, 'candidate_id');
    }

    public function onboarding(): HasMany
    {
        return $this->hasMany(Onboarding::class, 'candidate_id');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('current_status', $status);
    }

    public function scopeNotHired($query)
    {
        return $query->whereNotIn('current_status', ['Hired']);
    }

    public function scopeNotAssigned($query)
    {
        return $query->whereDoesntHave('candidateJobs');
    }

    // Helper methods
    public function getResumeUrl()
    {
        return $this->resume_path ? asset('storage/' . $this->resume_path) : null;
    }

    public function isAvailableForAssignment()
    {
        return !in_array($this->current_status, ['Hired', 'Rejected']);
    }

    public function getSkillsList()
    {
        return $this->skills->pluck('skill_name')->toArray();
    }
} 