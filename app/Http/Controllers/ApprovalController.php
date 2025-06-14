<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\Approval;
use App\Models\Group;
use App\Services\EmailService;

class ApprovalController extends Controller
{
    public function myApprovals()
    {
        // Check Microsoft Graph session authentication
        $user = Session::get('user');
        if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $userEmail = $user['profile']['userPrincipalName'] ?? $user['profile']['mail'] ?? null;
        $userName = $user['profile']['displayName'] ?? $user['profile']['name'] ?? 'Unknown User';
        
        if (!$userEmail) {
            return response()->json(['error' => 'User email not found'], 400);
        }

        // Get user's groups from Microsoft Graph session
        $userGroups = $user['groups']['value'] ?? $user['groups'] ?? [];
        $userGroupNames = [];
        
        // Extract group names from Microsoft Graph data
        foreach ($userGroups as $group) {
            if (isset($group['displayName'])) {
                $userGroupNames[] = $group['displayName'];
            }
        }

        Log::info('ApprovalController@myApprovals', [
            'user_email' => $userEmail,
            'user_groups' => $userGroupNames
        ]);

        if (empty($userGroupNames)) {
            Log::info('User has no groups, returning empty approvals');
            return response()->json([]);
        }

        // Get groups with priorities from database for hierarchy
        $groupPriorities = Group::whereIn('name', $userGroupNames)
            ->pluck('priority', 'name')
            ->toArray();

        Log::info('User group priorities', ['group_priorities' => $groupPriorities]);

        // Find pending approvals that this user can action
        $pendingApprovals = Approval::with(['memo'])
            ->where('status', 'pending')
            ->whereIn('required_group_name', $userGroupNames)
            ->get()
            ->filter(function($approval) use ($userGroupNames, $groupPriorities) {
                // Check if this approval can be processed by the user's groups
                return $approval->canBeApprovedByGroup($userGroupNames, $groupPriorities);
            })
            ->map(function($approval) {
                return [
                    'id' => $approval->id,
                    'status' => $approval->status,
                    'required_group' => $approval->required_group_name,
                    'group_priority' => $approval->group_priority,
                    'memo' => [
                        'id' => $approval->memo->id,
                        'description' => $approval->memo->description,
                        'issued_on' => $approval->memo->issued_on,
                        'document_path' => $approval->memo->document_path,
                        'raiser' => [
                            'name' => $approval->memo->raised_by_name ?? 'Unknown User',
                            'email' => $approval->memo->raised_by_email ?? 'unknown@company.com'
                        ]
                    ]
                ];
            })
            ->values();

        Log::info('Filtered pending approvals', [
            'total_found' => $pendingApprovals->count(),
            'approval_ids' => $pendingApprovals->pluck('id')->toArray()
        ]);

        return response()->json($pendingApprovals);
    }

    public function approve($id)
    {
        // Check Microsoft Graph session authentication
        $user = Session::get('user');
        if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $userEmail = $user['profile']['userPrincipalName'] ?? $user['profile']['mail'] ?? null;
        $userName = $user['profile']['displayName'] ?? $user['profile']['name'] ?? 'Unknown User';
        
        // Get user's groups
        $userGroups = $user['groups']['value'] ?? $user['groups'] ?? [];
        $userGroupNames = [];
        foreach ($userGroups as $group) {
            if (isset($group['displayName'])) {
                $userGroupNames[] = $group['displayName'];
            }
        }

        $approval = Approval::findOrFail($id);
        
        // Authorization check
        if (!in_array($approval->required_group_name, $userGroupNames)) {
            return response()->json(['error' => 'You are not authorized to approve for this group'], 403);
        }

        // Check if this approval can be processed (hierarchy check)
        $groupPriorities = Group::whereIn('name', $userGroupNames)
            ->pluck('priority', 'name')
            ->toArray();
            
        if (!$approval->canBeApprovedByGroup($userGroupNames, $groupPriorities)) {
            return response()->json(['error' => 'Cannot approve: higher priority groups must approve first'], 403);
        }
        
        // Approve the memo for this group
        $approval->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by_email' => $userEmail,
            'approved_by_name' => $userName,
        ]);

        Log::info('Approval completed', [
            'approval_id' => $approval->id,
            'group' => $approval->required_group_name,
            'approved_by' => $userName,
            'memo_id' => $approval->memo_id
        ]);
        
        return response()->json([
            'message' => 'Approved successfully',
            'approved_by' => $userName,
            'group' => $approval->required_group_name
        ]);
    }

    public function decline(Request $request, $id)
    {
        $request->validate(['comment' => 'required|string']);
        
        // Check Microsoft Graph session authentication
        $user = Session::get('user');
        if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $userEmail = $user['profile']['userPrincipalName'] ?? $user['profile']['mail'] ?? null;
        $userName = $user['profile']['displayName'] ?? $user['profile']['name'] ?? 'Unknown User';
        
        // Get user's groups
        $userGroups = $user['groups']['value'] ?? $user['groups'] ?? [];
        $userGroupNames = [];
        foreach ($userGroups as $group) {
            if (isset($group['displayName'])) {
                $userGroupNames[] = $group['displayName'];
            }
        }

        $approval = Approval::with('memo')->findOrFail($id);
        
        // Authorization check
        if (!in_array($approval->required_group_name, $userGroupNames)) {
            return response()->json(['error' => 'You are not authorized to decline for this group'], 403);
        }

        // Check if this approval can be processed (hierarchy check)
        $groupPriorities = Group::whereIn('name', $userGroupNames)
            ->pluck('priority', 'name')
            ->toArray();
            
        if (!$approval->canBeApprovedByGroup($userGroupNames, $groupPriorities)) {
            return response()->json(['error' => 'Cannot decline: higher priority groups must approve first'], 403);
        }
        
        // Decline the memo for this group
        $approval->update([
            'status' => 'declined',
            'comment' => $request->comment,
            'declined_by_email' => $userEmail,
            'declined_by_name' => $userName,
        ]);

        Log::info('Approval declined', [
            'approval_id' => $approval->id,
            'group' => $approval->required_group_name,
            'declined_by' => $userName,
            'reason' => $request->comment,
            'memo_id' => $approval->memo_id
        ]);

        // Send email notification to the memo raiser
        try {
            $emailService = new EmailService();
            $emailSent = $emailService->sendDeclineNotification(
                $approval->memo,
                $request->comment,
                $userName,
                $userEmail,
                $approval->required_group_name
            );

            if ($emailSent) {
                Log::info('Decline notification email sent successfully', [
                    'memo_id' => $approval->memo_id,
                    'recipient' => $approval->memo->raised_by_email
                ]);
            } else {
                Log::warning('Failed to send decline notification email', [
                    'memo_id' => $approval->memo_id,
                    'recipient' => $approval->memo->raised_by_email
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error sending decline notification email', [
                'error' => $e->getMessage(),
                'memo_id' => $approval->memo_id
            ]);
        }

        return response()->json([
            'message' => 'Declined successfully and notification email has been sent',
            'declined_by' => $userName,
            'group' => $approval->required_group_name,
            'email_sent' => $emailSent ?? false
        ]);
    }

    // TODO: Implement email notification using Microsoft Graph
    private function sendDeclineNotification($raizerEmail, $memoDescription, $declineReason, $declinerName, $groupName)
    {
        // Use Microsoft Graph Mail API to send notification
        // Subject: "Memo Declined by {$groupName}: {$memoDescription}"
        // Body: "Your memo has been declined by {$declinerName} from {$groupName}. Reason: {$declineReason}"
    }
} 