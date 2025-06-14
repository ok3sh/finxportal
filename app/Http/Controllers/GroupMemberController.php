<?php

namespace App\Http\Controllers;

use App\Services\MicrosoftGroupSyncService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class GroupMemberController extends Controller
{
    private $syncService;

    public function __construct()
    {
        $this->syncService = new MicrosoftGroupSyncService();
    }

    public function index()
    {
        try {
            Log::info('GroupMemberController: Getting group members directly from Microsoft Graph');
            
            // Fetch directly from Microsoft Graph API every time
            $members = $this->syncService->getAllGroupMembers();

            Log::info('GroupMemberController: Returning members', [
                'total_members' => count($members),
                'sample' => array_slice($members, 0, 3) // Log first 3 for debugging
            ]);

            return response()->json($members);

        } catch (\Exception $e) {
            Log::error('GroupMemberController: Error fetching group members', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return empty array on error - frontend can handle gracefully
            return response()->json([]);
        }
    }

    /**
     * Get statistics about current Graph API data
     */
    public function getStats()
    {
        try {
            $stats = $this->syncService->getStats();
            
            return response()->json($stats);

        } catch (\Exception $e) {
            Log::error('GroupMemberController: Failed to get stats', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to get statistics'
            ], 500);
        }
    }

    /**
     * Get Azure AD groups for selection interface
     */
    public function getAzureGroups()
    {
        try {
            Log::info('GroupMemberController: Getting Azure AD groups for selection');
            
            // Check if we have a valid access token
            $tokenData = Session::get('token');
            if (!$tokenData || !isset($tokenData['access_token'])) {
                Log::warning('GroupMemberController: No access token available');
                return response()->json([]);
            }

            $accessToken = $tokenData['access_token'];
            $client = new \GuzzleHttp\Client(['timeout' => 30.0]);

            // Fetch all groups from Microsoft Graph
            $response = $client->get('https://graph.microsoft.com/v1.0/groups', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    '$select' => 'id,displayName,description'
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            $groups = $data['value'] ?? [];

            Log::info('GroupMemberController: Found Azure AD groups', [
                'total_groups' => count($groups)
            ]);

            return response()->json($groups);

        } catch (\Exception $e) {
            Log::error('GroupMemberController: Error fetching Azure AD groups', [
                'error' => $e->getMessage()
            ]);
            
            // Return empty array on error - frontend can handle gracefully
            return response()->json([]);
        }
    }
} 