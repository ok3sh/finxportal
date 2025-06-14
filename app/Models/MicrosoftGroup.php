<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MicrosoftGroup extends Model
{
    protected $fillable = [
        'azure_group_id',
        'display_name', 
        'description',
        'members',
        'member_count',
        'last_synced_at'
    ];

    protected $casts = [
        'members' => 'array',
        'last_synced_at' => 'datetime'
    ];

    /**
     * Check if the group data is stale and needs refresh
     */
    public function isStale(int $maxAgeMinutes = 60): bool
    {
        return $this->last_synced_at->addMinutes($maxAgeMinutes)->isPast();
    }

    /**
     * Update group with fresh data from Microsoft Graph
     */
    public function updateFromGraphData(array $graphGroup, array $members): void
    {
        $this->update([
            'display_name' => $graphGroup['displayName'],
            'description' => $graphGroup['description'] ?? null,
            'members' => $members,
            'member_count' => count($members),
            'last_synced_at' => now()
        ]);
    }

    /**
     * Get all members with their details including priority from local groups table
     */
    public function getMembersWithPriority(): array
    {
        // Get priority from local groups table for hierarchy
        $localGroup = \App\Models\Group::where('name', $this->display_name)->first();
        $priority = $localGroup ? $localGroup->priority : 999; // Default low priority

        $members = [];
        foreach ($this->members as $member) {
            $members[] = [
                'id' => $member['id'],
                'name' => $member['displayName'] ?? $member['userPrincipalName'],
                'email' => $member['userPrincipalName'] ?? $member['mail'],
                'group_name' => $this->display_name,
                'group_id' => $this->azure_group_id,
                'group_priority' => $priority // Include priority for hierarchy
            ];
        }
        return $members;
    }

    /**
     * Find group by Azure AD group ID
     */
    public static function findByAzureId(string $azureGroupId): ?self
    {
        return static::where('azure_group_id', $azureGroupId)->first();
    }

    /**
     * Get groups that need refresh (older than specified minutes)
     */
    public static function getStaleGroups(int $maxAgeMinutes = 60): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('last_synced_at', '<', now()->subMinutes($maxAgeMinutes))->get();
    }
} 