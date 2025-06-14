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

            $tokenData = Session::get('token');
            if (!$tokenData || !isset($tokenData['access_token'])) {
                return response()->json(['error' => 'No access token'], 401);
            }

            // Create a test memo object
            $testMemo = (object) [
                'id' => 999,
                'description' => 'Test Email Functionality',
                'raised_by_name' => 'Test User',
                'raised_by_email' => $user['profile']['userPrincipalName'] ?? $user['profile']['mail'],
                'document_path' => null,
                'created_at' => now()
            ];

            $emailService = new \App\Services\EmailService();
            $result = $emailService->sendDeclineNotification(
                $testMemo,
                'This is a test decline reason to verify email functionality is working correctly.',
                $user['profile']['displayName'] ?? 'Test Decliner',
                $user['profile']['userPrincipalName'] ?? $user['profile']['mail'],
                'Test Group'
            );

            return response()->json([
                'email_sent' => $result,
                'test_memo' => $testMemo,
                'user_email' => $user['profile']['userPrincipalName'] ?? $user['profile']['mail'],
                'has_token' => !empty($tokenData['access_token'])
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
