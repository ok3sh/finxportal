<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobMaster extends Model
{
    protected $table = 'jobs_master';

    protected $fillable = [
        'job_title',
        'department',
        'location',
        'hiring_manager',
        'job_description',
        'experience_requirements',
        'education_requirements',
        'number_of_openings',
        'salary_min',
        'salary_max',
        'status'
    ];

    protected $casts = [
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'number_of_openings' => 'integer'
    ];

    // Relationships
    public function candidateJobs(): HasMany
    {
        return $this->hasMany(CandidateJob::class, 'job_id');
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class, 'job_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class, 'job_id');
    }

    public function onboarding(): HasMany
    {
        return $this->hasMany(Onboarding::class, 'job_id');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'Open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'Closed');
    }

    // Helper methods
    public function isOpen()
    {
        return $this->status === 'Open';
    }

    public function getAssignedCandidatesCount()
    {
        return $this->candidateJobs()->count();
    }

    public function getHiredCandidatesCount()
    {
        return $this->candidateJobs()->where('assignment_status', 'Hired')->count();
    }
} 