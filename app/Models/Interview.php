<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interview extends Model
{
    protected $fillable = [
        'candidate_id',
        'job_id',
        'interviewer_emails',
        'interview_datetime',
        'mode',
        'meeting_link_or_location',
        'status',
        'notes',
        'feedback',
        'result',
        'created_by_email'
    ];

    protected $casts = [
        'interviewer_emails' => 'array',
        'interview_datetime' => 'datetime'
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
    public function scopeScheduled($query)
    {
        return $query->where('status', 'Scheduled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'Completed');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'Scheduled')
                    ->where('interview_datetime', '>', now());
    }

    public function scopePassed($query)
    {
        return $query->where('result', 'Pass');
    }

    // Helper methods
    public function isUpcoming()
    {
        return $this->status === 'Scheduled' && $this->interview_datetime > now();
    }

    public function isPassed()
    {
        return $this->result === 'Pass';
    }

    public function getInterviewersList()
    {
        return is_array($this->interviewer_emails) ? implode(', ', $this->interviewer_emails) : '';
    }
} 