<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Memo;
use App\Models\Approval;
use App\Models\User;
use App\Services\MicrosoftGroupSyncService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Group;

class MemoController extends Controller
{
    private $syncService;

    public function __construct()
    {
        $this->syncService = new MicrosoftGroupSyncService();
    }

    public function store(Request $request)
    {
        try {
            // Check Microsoft Graph session authentication
            $user = Session::get('user');
            if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
                Log::error('User not authenticated via Microsoft Graph');
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $userEmail = $user['profile']['userPrincipalName'] ?? $user['profile']['mail'] ?? null;
            $userName = $user['profile']['displayName'] ?? $user['profile']['name'] ?? 'Unknown User';

            Log::info('MemoController@store called', [
                'user_email' => $userEmail,
                'user_name' => $userName,
                'request_data' => $request->except(['document'])
            ]);

            // Log validation attempt
            Log::info('Starting validation');
            
            $request->validate([
                'document' => 'required|file|max:10240', // 10MB max
                'description' => 'required|string|max:1000',
                'approvers' => 'required|array|min:1',
                'approvers.*' => 'required|string', // Now expecting group names instead of user IDs
            ]);

            Log::info('Validation passed');

            // Handle file upload
            $file = $request->file('document');
            Log::info('File info', [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);

            $path = $file->store('memos');
            Log::info('File stored at: ' . $path);

            // Create memo
            $memo = Memo::create([
                'description' => $request->description,
                'document_path' => $path,
                'raised_by_name' => $userName,
                'raised_by_email' => $userEmail,
                'issued_on' => now()->toDateString(),
            ]);

            Log::info('Memo created', ['memo_id' => $memo->id]);

            // Get required groups from selected group names
            $requiredGroups = $this->getRequiredGroupsFromNames($request->approvers);

            Log::info('Required groups determined', [
                'groups' => array_keys($requiredGroups),
                'total_groups' => count($requiredGroups)
            ]);

            // Create approval records for each required group with hierarchy
            foreach ($requiredGroups as $groupName => $groupInfo) {
                Approval::create([
                    'memo_id' => $memo->id,
                    'required_group_name' => $groupName,
                    'group_priority' => $groupInfo['priority'], // Include priority for hierarchy
                    'status' => 'pending'
                ]);
                
                Log::info("Created approval for group: {$groupName} (priority {$groupInfo['priority']})");
            }

            return response()->json([
                'message' => 'Memo raised successfully',
                'memo_id' => $memo->id,
                'approvals_created' => count($requiredGroups),
                'required_groups' => array_keys($requiredGroups),
                'hierarchy' => array_map(function($group) {
                    return ['name' => $group['name'], 'priority' => $group['priority']];
                }, $requiredGroups)
            ], 201);

        } catch (\Exception $e) {
            Log::error('MemoController@store failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to raise ticket', 
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function getRequiredGroupsFromNames($groupNames)
    {
        $requiredGroups = [];
        
        foreach ($groupNames as $index => $groupName) {
            $requiredGroups[$groupName] = [
                'name' => $groupName,
                'priority' => $index + 1, // Use selection order as priority
                'group_id' => null
            ];
            
            Log::info("Group '{$groupName}' requires approval (priority " . ($index + 1) . ")");
        }

        return $requiredGroups;
    }
} 