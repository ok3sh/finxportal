<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class EmployeeController extends Controller
{
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30.0,
            'connect_timeout' => 10.0
        ]);
    }

    /**
     * Get all employees from Microsoft Graph API
     */
    public function index()
    {
        try {
            // Check Microsoft Graph session authentication
            $user = Session::get('user');
            $tokenData = Session::get('token');
            
            if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            if (!$tokenData || !isset($tokenData['access_token'])) {
                return response()->json(['error' => 'No access token available'], 401);
            }

            $accessToken = $tokenData['access_token'];
            
            Log::info('EmployeeController: Fetching all employees from Microsoft Graph');

            // Fetch all users from Microsoft Graph
            $employees = $this->fetchAllEmployees($accessToken);

            Log::info('EmployeeController: Returning employees', [
                'total_employees' => count($employees)
            ]);

            return response()->json($employees);

        } catch (\Exception $e) {
            Log::error('EmployeeController: Error fetching employees', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // Return fallback data in case of error
            return response()->json($this->getFallbackEmployees());
        }
    }

    /**
     * Fetch all employees from Microsoft Graph
     */
    private function fetchAllEmployees($accessToken)
    {
        try {
            Log::info('EmployeeController: Fetching users from Microsoft Graph API');
            
            // Fetch users from Microsoft Graph
            $response = $this->client->get('https://graph.microsoft.com/v1.0/users', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    '$select' => 'id,displayName,userPrincipalName,mail,jobTitle,department,officeLocation',
                    '$filter' => "accountEnabled eq true and userType eq 'Member'", // Only active member accounts
                    '$top' => 999 // Get up to 999 users (Microsoft Graph limit)
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            $users = $data['value'] ?? [];

            Log::info('EmployeeController: Found users from Graph', [
                'count' => count($users)
            ]);

            // Also fetch group memberships to add group information
            $employeesWithGroups = $this->enrichWithGroupData($accessToken, $users);

            return $employeesWithGroups;

        } catch (RequestException $e) {
            Log::error('EmployeeController: Microsoft Graph API error', [
                'error' => $e->getMessage(),
                'status_code' => $e->getResponse() ? $e->getResponse()->getStatusCode() : 'unknown'
            ]);
            
            return $this->getFallbackEmployees();
        }
    }

    /**
     * Enrich user data with group membership information
     */
    private function enrichWithGroupData($accessToken, $users)
    {
        $employees = [];
        
        foreach ($users as $user) {
            $employee = [
                'id' => $user['id'],
                'name' => $user['displayName'] ?? $user['userPrincipalName'],
                'email' => $user['userPrincipalName'] ?? $user['mail'] ?? '',
                'job_title' => $user['jobTitle'] ?? null,
                'department' => $user['department'] ?? null,
                'office_location' => $user['officeLocation'] ?? null,
                'group_name' => null // Will be populated below
            ];

            // Try to get the user's primary group membership
            try {
                $groupResponse = $this->client->get("https://graph.microsoft.com/v1.0/users/{$user['id']}/memberOf", [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json'
                    ],
                    'query' => [
                        '$select' => 'displayName',
                        '$top' => 1 // Just get the first group
                    ]
                ]);

                $groupData = json_decode($groupResponse->getBody(), true);
                $groups = $groupData['value'] ?? [];
                
                if (!empty($groups)) {
                    $employee['group_name'] = $groups[0]['displayName'];
                }

            } catch (\Exception $e) {
                // If group fetch fails, just continue without group info
                Log::warning('EmployeeController: Failed to fetch groups for user', [
                    'user_id' => $user['id'],
                    'error' => $e->getMessage()
                ]);
            }

            $employees[] = $employee;
        }

        return $employees;
    }

    /**
     * Fallback employees in case of API failures
     */
    private function getFallbackEmployees()
    {
        Log::info('EmployeeController: Microsoft Graph API unavailable, returning empty employee list');
        
        // Return empty array instead of dummy data
        // Frontend will handle showing "not found" message
        return [];
    }
} 