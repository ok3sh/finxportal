<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GroupMemberController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\MemoController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\SharedCalendarController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AccessControlController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\HRAdminController;

use Illuminate\Support\Facades\Session;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/callback', [AuthController::class, 'callback']);


Route::prefix('api')->group(function () {
    Route::get('/group-members', [GroupMemberController::class, 'index']);
    Route::get('/my-approvals', [ApprovalController::class, 'myApprovals']);
    Route::get('/auth/status', [AuthController::class, 'status']);
    Route::get('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/calendar/refresh', [AuthController::class, 'refreshCalendar']);
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::get('/links', [LinkController::class, 'index']);
    Route::get('/links/access-info', [LinkController::class, 'getAccessInfo']);
    Route::get('/links/rightpanel', [LinkController::class, 'getRightPanelLink']);
    Route::get('/shared-calendar', [SharedCalendarController::class, 'index']);
    Route::get('/employees', [EmployeeController::class, 'index']);

    // Memo approval system routes
    Route::get('/groups', [GroupMemberController::class, 'getAzureGroups']);
    Route::post('/memos', [MemoController::class, 'store']);
    Route::post('/approvals/{id}/approve', [ApprovalController::class, 'approve']);
    Route::post('/approvals/{id}/decline', [ApprovalController::class, 'decline']);

    // Admin routes for Microsoft Graph statistics
    Route::get('/admin/groups/stats', [GroupMemberController::class, 'getStats']);

    // Access Control Management routes
    Route::get('/admin/access-control', [AccessControlController::class, 'index']);
    Route::post('/admin/access-control', [AccessControlController::class, 'store']);
    Route::put('/admin/access-control/{id}', [AccessControlController::class, 'update']);
    Route::delete('/admin/access-control/{id}', [AccessControlController::class, 'destroy']);
    Route::get('/admin/access-control/stats', [AccessControlController::class, 'getStats']);

    // Asset Management routes
    Route::get('/assets', [AssetController::class, 'index']);
    Route::post('/assets', [AssetController::class, 'store']);
    Route::get('/assets/types', [AssetController::class, 'getAssetTypes']);
    Route::get('/assets/locations', [AssetController::class, 'getLocations']);

    // Asset allocation operations
    Route::post('/assets/allocate', [AssetController::class, 'allocate']);
    Route::post('/assets/deallocate', [AssetController::class, 'deallocate']);
    Route::post('/assets/reallocate', [AssetController::class, 'reallocate']);
    Route::post('/assets/decommission', [AssetController::class, 'decommission']);

    // HR Management routes
    Route::prefix('hr')->group(function () {
        // Core HR operations
        Route::post('/jobs', [HRAdminController::class, 'createJob']);
        Route::post('/candidates', [HRAdminController::class, 'addCandidate']);
        Route::post('/assign-to-job', [HRAdminController::class, 'assignToJob']);
        Route::post('/approve-candidate', [HRAdminController::class, 'approveCandidate']);
        Route::post('/schedule-interview', [HRAdminController::class, 'scheduleInterview']);
        Route::post('/send-offer', [HRAdminController::class, 'sendOffer']);
        Route::post('/start-onboarding', [HRAdminController::class, 'startOnboarding']);
        Route::post('/mark-resignation', [HRAdminController::class, 'markResignation']);

        // Data retrieval endpoints
        Route::get('/jobs', [HRAdminController::class, 'getJobs']);
        Route::get('/candidates', [HRAdminController::class, 'getCandidates']);
        Route::get('/candidate-sources', [HRAdminController::class, 'getCandidateSources']);
        Route::get('/candidate-skills', [HRAdminController::class, 'getCandidateSkills']);
        Route::get('/available-candidates', [HRAdminController::class, 'getAvailableCandidates']);
        Route::get('/candidates-for-approval', [HRAdminController::class, 'getCandidatesForApproval']);
        Route::get('/verified-candidates', [HRAdminController::class, 'getVerifiedCandidates']);
        Route::get('/active-employees', [HRAdminController::class, 'getActiveEmployees']);
    });

    // Debug routes for testing and development
    Route::get('/debug/auth', function() {
        return response()->json([
            'authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
            'session_data' => session()->all(),
            'timestamp' => now()
        ]);
    });

    // Debug: Check user groups and access control
    Route::get('/debug/user-groups', function() {
        $user = \Illuminate\Support\Facades\Session::get('user');
        $userGroups = [];
        
        if ($user && isset($user['authenticated']) && $user['authenticated']) {
            $userGraphGroups = $user['groups']['value'] ?? $user['groups'] ?? [];
            foreach ($userGraphGroups as $group) {
                if (isset($group['displayName'])) {
                    $userGroups[] = $group['displayName'];
                }
            }
        }

        // Check what access control rules exist for these groups
        $accessRules = \App\Models\GroupPersonalizedLink::whereIn('microsoft_group_name', $userGroups)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'user_groups' => $userGroups,
            'access_rules_found' => $accessRules->map(function($rule) {
                return [
                    'group' => $rule->microsoft_group_name,
                    'link_name' => $rule->link_name,
                    'link_url' => $rule->link_url,
                    'replaces' => $rule->replaces_link
                ];
            }),
            'total_rules' => $accessRules->count(),
            'should_replace_outlook' => $accessRules->where('replaces_link', 'outlook')->count() > 0
        ]);
    });

    Route::post('/debug/memo-test', function(\Illuminate\Http\Request $request) {
        try {
            // Check Microsoft Graph session authentication (same as other API endpoints)
            $user = \Illuminate\Support\Facades\Session::get('user');
            $isAuthenticated = !empty($user) && isset($user['authenticated']) && $user['authenticated'];
            
            return response()->json([
                'success' => true,
                'authenticated' => $isAuthenticated,
                'user_info' => $isAuthenticated ? [
                    'email' => $user['profile']['userPrincipalName'] ?? $user['profile']['mail'] ?? null,
                    'name' => $user['profile']['displayName'] ?? $user['profile']['name'] ?? 'Unknown'
                ] : null,
                'request_data' => $request->except(['document']),
                'has_file' => $request->hasFile('document'),
                'file_info' => $request->hasFile('document') ? [
                    'name' => $request->file('document')->getClientOriginalName(),
                    'size' => $request->file('document')->getSize()
                ] : null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    });

    Route::get('/debug/workflow', function() {
        try {
            $user = \Illuminate\Support\Facades\Session::get('user');
            if (!$user) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            // Get group members (what User 1 sees)
            $groupMemberController = new \App\Http\Controllers\GroupMemberController();
            $membersResponse = $groupMemberController->index();
            $members = json_decode($membersResponse->getContent(), true);

            // Get pending approvals (what User 2 sees)
            $approvalController = new \App\Http\Controllers\ApprovalController();
            $approvalsResponse = $approvalController->myApprovals();
            $approvals = json_decode($approvalsResponse->getContent(), true);

            return response()->json([
                'current_user' => [
                    'email' => $user['profile']['userPrincipalName'] ?? $user['profile']['mail'] ?? null,
                    'name' => $user['profile']['displayName'] ?? $user['profile']['name'] ?? 'Unknown',
                    'groups' => $user['groups']['value'] ?? $user['groups'] ?? []
                ],
                'available_approvers' => count($members),
                'pending_approvals' => count($approvals),
                'members_sample' => array_slice($members, 0, 3), // First 3 members
                'approvals_sample' => array_slice($approvals, 0, 3), // First 3 approvals
                'workflow_status' => 'Ready for testing'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Workflow debug failed',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    });

    // Test email functionality
    Route::get('/api/debug/test-email', function () {
        try {
            $user = Session::get('user');
            if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            $userEmail = $user['profile']['userPrincipalName'] ?? $user['profile']['mail'];
            $userName = $user['profile']['displayName'] ?? 'Test User';

            Log::info('Debug noreply email test started', [
                'recipient_email' => $userEmail,
                'recipient_name' => $userName
            ]);

            // Test noreply email send using application permissions
            $emailService = new \App\Services\EmailService();
            $result = $emailService->sendEmailFromNoreply(
                $userEmail, // Send to self for testing
                $userName,
                'Test Email from FinFinity Portal - Noreply',
                '<h2>🚀 Noreply Email Test</h2>
                <p>This is a test email sent from <strong>noreply@finfinity.co.in</strong> using Application permissions.</p>
                <p>If you receive this email, the noreply email integration is working correctly!</p>
                <ul>
                    <li><strong>Sender:</strong> noreply@finfinity.co.in</li>
                    <li><strong>Method:</strong> Application Permissions (Client Credentials)</li>
                    <li><strong>Timestamp:</strong> ' . now()->toDateTimeString() . '</li>
                </ul>
                <p style="color: #28a745;">✅ Noreply email system is functional!</p>'
            );

            return response()->json([
                'email_sent' => $result,
                'sender' => 'noreply@finfinity.co.in',
                'recipient' => $userEmail,
                'method' => 'Application Permissions (Client Credentials)',
                'message' => $result ? 'Noreply email sent successfully' : 'Noreply email failed to send - check logs for details'
            ]);

        } catch (\Exception $e) {
            Log::error('Debug noreply email test failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    });

    Route::get('/api/debug/token-info', function () {
        try {
            $user = Session::get('user');
            $tokenData = Session::get('token');
            
            if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            if (!$tokenData || !isset($tokenData['access_token'])) {
                return response()->json(['error' => 'No access token'], 401);
            }

            // Try to get token info from Microsoft Graph
            $client = new \GuzzleHttp\Client(['timeout' => 30.0]);
            
            try {
                // Get current user info to test token
                $response = $client->get('https://graph.microsoft.com/v1.0/me', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $tokenData['access_token'],
                        'Content-Type' => 'application/json'
                    ]
                ]);
                
                $userInfo = json_decode($response->getBody(), true);
                $tokenValid = true;
                
            } catch (\Exception $e) {
                $userInfo = ['error' => $e->getMessage()];
                $tokenValid = false;
            }

            return response()->json([
                'token_valid' => $tokenValid,
                'user_info' => $userInfo,
                'token_data' => [
                    'has_access_token' => isset($tokenData['access_token']),
                    'has_refresh_token' => isset($tokenData['refresh_token']),
                    'token_length' => strlen($tokenData['access_token'] ?? ''),
                    'expires_in' => $tokenData['expires_in'] ?? 'unknown',
                    'scope' => $tokenData['scope'] ?? 'unknown',
                    'token_type' => $tokenData['token_type'] ?? 'unknown'
                ],
                'session_user' => [
                    'email' => $user['profile']['userPrincipalName'] ?? $user['profile']['mail'] ?? 'unknown',
                    'name' => $user['profile']['displayName'] ?? 'unknown',
                    'groups_count' => count($user['groups']['value'] ?? [])
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    });

    Route::get('/api/debug/check-permissions', function () {
        try {
            $user = Session::get('user');
            $tokenData = Session::get('token');
            
            if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            if (!$tokenData || !isset($tokenData['access_token'])) {
                return response()->json(['error' => 'No access token'], 401);
            }

            $client = new \GuzzleHttp\Client(['timeout' => 30.0]);
            $results = [];

            // Test 1: Basic user info (should work)
            try {
                $response = $client->get('https://graph.microsoft.com/v1.0/me', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $tokenData['access_token'],
                        'Content-Type' => 'application/json'
                    ]
                ]);
                $results['user_read'] = [
                    'status' => 'SUCCESS',
                    'status_code' => $response->getStatusCode(),
                    'user_email' => json_decode($response->getBody(), true)['userPrincipalName'] ?? 'unknown'
                ];
            } catch (\Exception $e) {
                $results['user_read'] = [
                    'status' => 'FAILED', 
                    'error' => $e->getMessage()
                ];
            }

            // Test 2: Check if user has a mailbox
            try {
                $response = $client->get('https://graph.microsoft.com/v1.0/me/mailboxSettings', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $tokenData['access_token'],
                        'Content-Type' => 'application/json'
                    ]
                ]);
                $results['mailbox_access'] = [
                    'status' => 'SUCCESS',
                    'status_code' => $response->getStatusCode(),
                    'has_mailbox' => true
                ];
            } catch (\Exception $e) {
                $results['mailbox_access'] = [
                    'status' => 'FAILED',
                    'error' => $e->getMessage()
                ];
            }

            // Test 3: Try to get mail folders (tests Mail permissions)
            try {
                $response = $client->get('https://graph.microsoft.com/v1.0/me/mailFolders', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $tokenData['access_token'],
                        'Content-Type' => 'application/json'
                    ]
                ]);
                $results['mail_folders'] = [
                    'status' => 'SUCCESS',
                    'status_code' => $response->getStatusCode()
                ];
            } catch (\Exception $e) {
                $results['mail_folders'] = [
                    'status' => 'FAILED',
                    'error' => $e->getMessage()
                ];
            }

            return response()->json([
                'token_info' => [
                    'has_access_token' => !empty($tokenData['access_token']),
                    'token_length' => strlen($tokenData['access_token'] ?? ''),
                    'scope' => $tokenData['scope'] ?? 'not available',
                    'token_type' => $tokenData['token_type'] ?? 'unknown'
                ],
                'permission_tests' => $results,
                'user_email' => $user['profile']['userPrincipalName'] ?? $user['profile']['mail'] ?? 'unknown',
                'message' => 'Check which permissions are working'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    });
});
