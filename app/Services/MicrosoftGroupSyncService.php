<?php

namespace App\Services;

use App\Models\Group;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class MicrosoftGroupSyncService
{
    private $client;
    private $accessToken;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30.0, // Increased timeout for direct API calls
            'connect_timeout' => 10.0
        ]);
    }

    /**
     * Get all group members directly from Microsoft Graph API
     */
    public function getAllGroupMembers(): array
    {
        try {
            // Check if we have a valid access token
            $tokenData = Session::get('token');
            if (!$tokenData || !isset($tokenData['access_token'])) {
                Log::warning('MicrosoftGroupSyncService: No access token available');
                return $this->fallbackToSampleData();
            }

            $this->accessToken = $tokenData['access_token'];

            Log::info('MicrosoftGroupSyncService: Fetching groups directly from Microsoft Graph');
            
            // Fetch all groups and their members directly from Graph API
            $graphGroups = $this->fetchAllGraphGroups();
            
            Log::info('MicrosoftGroupSyncService: Found groups from Graph', [
                'count' => count($graphGroups)
            ]);

            $allMembers = [];

            foreach ($graphGroups as $graphGroup) {
                $groupName = $graphGroup['displayName'];
                $groupId = $graphGroup['id'];
                
                // Get priority from local groups table for hierarchy
                $localGroup = Group::where('name', $groupName)->first();
                $priority = $localGroup ? $localGroup->priority : 999; // Default low priority
                
                // Fetch members for this group
                $members = $this->fetchGroupMembers($groupId);
                
                // Add members to results with group info and priority
                foreach ($members as $member) {
                    $allMembers[] = [
                        'id' => $member['id'],
                        'name' => $member['displayName'] ?? $member['userPrincipalName'],
                        'email' => $member['userPrincipalName'] ?? $member['mail'],
                        'group_name' => $groupName,
                        'group_id' => $groupId,
                        'group_priority' => $priority // Include priority for hierarchy
                    ];
                }
                
                Log::info('MicrosoftGroupSyncService: Processed group', [
                    'group' => $groupName,
                    'members' => count($members),
                    'priority' => $priority
                ]);
            }

            Log::info('MicrosoftGroupSyncService: Returning direct API results', [
                'total_members' => count($allMembers),
                'groups' => count($graphGroups)
            ]);

            return $allMembers;

        } catch (\Exception $e) {
            Log::error('MicrosoftGroupSyncService: Error fetching from Graph API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->fallbackToSampleData();
        }
    }

    /**
     * Fetch all groups from Microsoft Graph
     */
    private function fetchAllGraphGroups(): array
    {
        $response = $this->client->get('https://graph.microsoft.com/v1.0/groups', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ],
            'query' => [
                '$select' => 'id,displayName,description,membershipType'
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['value'] ?? [];
    }

    /**
     * Fetch members for a specific group
     */
    private function fetchGroupMembers(string $groupId): array
    {
        try {
            $response = $this->client->get("https://graph.microsoft.com/v1.0/groups/{$groupId}/members", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    '$select' => 'id,displayName,userPrincipalName,mail,userType'
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['value'] ?? [];

        } catch (RequestException $e) {
            Log::warning('MicrosoftGroupSyncService: Failed to fetch group members', [
                'group_id' => $groupId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Fallback to empty data when Graph API is unavailable
     */
    private function fallbackToSampleData(): array
    {
        Log::info('MicrosoftGroupSyncService: Microsoft Graph API unavailable, returning empty data');
        
        // Return empty array instead of dummy data
        // Frontend will handle showing "no data available" message
        return [];
    }

    /**
     * Get current statistics (simplified for direct API)
     */
    public function getStats(): array
    {
        try {
            $members = $this->getAllGroupMembers();
            $groups = collect($members)->groupBy('group_name');
            
            return [
                'total_groups' => $groups->count(),
                'total_members' => count($members),
                'data_source' => 'Microsoft Graph API (Direct)',
                'last_fetch' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            return [
                'error' => 'Failed to get statistics',
                'message' => $e->getMessage()
            ];
        }
    }
} 