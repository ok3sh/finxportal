<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateJob extends Model
{
    protected $table = 'candidate_jobs';

    protected $fillable = [
        'candidate_id',
        'job_id',
        'assignment_status',
        'assignment_notes',
        'assigned_at'
    ];

    protected $casts = [
        'assigned_at' => 'datetime'
    ];

    // Relationships
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(CandidateMaster::class, 'candidate_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(JobMaster::class, 'job_id');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('assignment_status', $status);
    }

    public function scopeApplied($query)
    {
        return $query->where('assignment_status', 'Applied');
    }

    public function scopeVerified($query)
    {
        return $query->where('assignment_status', 'Verified');
    }

    public function scopeHired($query)
    {
        return $query->where('assignment_status', 'Hired');
    }

    // Helper methods
    public function canBeVerified()
    {
        return $this->assignment_status === 'Applied';
    }

    public function canScheduleInterview()
    {
        return $this->assignment_status === 'Verified';
    }
} 