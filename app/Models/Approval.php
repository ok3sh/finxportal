<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Approval extends Model
{
    protected $fillable = [
        'memo_id',
        'required_group_name',
        'group_priority',
        'status',
        'comment',
        'approved_at',
        'approved_by_email',
        'approved_by_name',
        'declined_by_email', 
        'declined_by_name'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function memo(): BelongsTo
    {
        return $this->belongsTo(Memo::class);
    }

    // Scope to get approvals for a specific group
    public function scopeForGroup($query, $groupName)
    {
        return $query->where('required_group_name', $groupName);
    }

    // Scope to get approvals at or below a priority level
    public function scopeAtPriority($query, $priority)
    {
        return $query->where('group_priority', $priority);
    }

    /**
     * Check if all higher priority approvals are completed for this memo
     */
    public function canBeApprovedByGroup($userGroupNames, $userGroupPriorities)
    {
        // Check if user belongs to the required group
        if (!in_array($this->required_group_name, $userGroupNames)) {
            return false;
        }

        // Check if all higher priority groups have approved
        $higherPriorityPending = Approval::where('memo_id', $this->memo_id)
            ->where('group_priority', '<', $this->group_priority)
            ->where('status', 'pending')
            ->exists();

        return !$higherPriorityPending;
    }
} 