<?php

namespace App\Http\Controllers;

use App\Models\GroupPersonalizedLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class AccessControlController extends Controller
{
    /**
     * Get all access control configurations
     */
    public function index()
    {
        try {
            $accessRules = GroupPersonalizedLink::active()
                ->orderBy('microsoft_group_name')
                ->orderBy('sort_order')
                ->get()
                ->groupBy('microsoft_group_name');

            $result = [];
            foreach ($accessRules as $groupName => $links) {
                $result[] = [
                    'group_name' => $groupName,
                    'links' => $links->map(function($link) {
                        return [
                            'id' => $link->id,
                            'name' => $link->link_name,
                            'url' => $link->link_url,
                            'logo' => $link->logo,
                            'background_color' => $link->background_color,
                            'sort_order' => $link->sort_order,
                            'replaces' => $link->replaces_link,
                            'type' => $link->replaces_link ? 'replacement' : 'additional',
                            'is_active' => $link->is_active
                        ];
                    })->values()
                ];
            }

            Log::info('AccessControlController: Fetched access control rules', [
                'groups_count' => count($result),
                'total_links' => GroupPersonalizedLink::count()
            ]);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('AccessControlController: Error fetching access control rules', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch access control rules',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new personalized link for a group
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'microsoft_group_name' => 'required|string|max:255',
                'link_name' => 'required|string|max:255',
                'link_url' => 'required|url|max:500',
                'link_logo' => 'nullable|string|max:255',
                'background_color' => 'nullable|string|max:10',
                'sort_order' => 'nullable|integer|min:1',
                'replaces_link' => 'nullable|string|max:255'
            ]);

            // Check for unique constraint (group + replaces_link combination)
            if ($request->replaces_link) {
                $existing = GroupPersonalizedLink::where('microsoft_group_name', $request->microsoft_group_name)
                    ->where('replaces_link', $request->replaces_link)
                    ->first();
                
                if ($existing) {
                    return response()->json([
                        'error' => 'A replacement link for this group and target already exists'
                    ], 400);
                }
            }

            $accessRule = GroupPersonalizedLink::create([
                'microsoft_group_name' => $request->microsoft_group_name,
                'link_name' => $request->link_name,
                'link_url' => $request->link_url,
                'link_logo' => $request->link_logo,
                'background_color' => $request->background_color ?? '#115948',
                'sort_order' => $request->sort_order ?? 1,
                'replaces_link' => $request->replaces_link,
                'is_active' => true
            ]);

            Log::info('AccessControlController: Created new access rule', [
                'id' => $accessRule->id,
                'group' => $accessRule->microsoft_group_name,
                'link' => $accessRule->link_name
            ]);

            return response()->json([
                'message' => 'Access control rule created successfully',
                'access_rule' => $accessRule
            ], 201);

        } catch (\Exception $e) {
            Log::error('AccessControlController: Error creating access rule', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to create access control rule',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing personalized link
     */
    public function update(Request $request, $id)
    {
        try {
            $accessRule = GroupPersonalizedLink::findOrFail($id);

            $request->validate([
                'microsoft_group_name' => 'sometimes|required|string|max:255',
                'link_name' => 'sometimes|required|string|max:255',
                'link_url' => 'sometimes|required|url|max:500',
                'link_logo' => 'nullable|string|max:255',
                'background_color' => 'nullable|string|max:10',
                'sort_order' => 'nullable|integer|min:1',
                'replaces_link' => 'nullable|string|max:255',
                'is_active' => 'sometimes|boolean'
            ]);

            $accessRule->update($request->only([
                'microsoft_group_name', 'link_name', 'link_url', 'link_logo',
                'background_color', 'sort_order', 'replaces_link', 'is_active'
            ]));

            Log::info('AccessControlController: Updated access rule', [
                'id' => $accessRule->id,
                'group' => $accessRule->microsoft_group_name
            ]);

            return response()->json([
                'message' => 'Access control rule updated successfully',
                'access_rule' => $accessRule
            ]);

        } catch (\Exception $e) {
            Log::error('AccessControlController: Error updating access rule', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to update access control rule',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a personalized link
     */
    public function destroy($id)
    {
        try {
            $accessRule = GroupPersonalizedLink::findOrFail($id);
            $accessRule->delete();

            Log::info('AccessControlController: Deleted access rule', [
                'id' => $id,
                'group' => $accessRule->microsoft_group_name,
                'link' => $accessRule->link_name
            ]);

            return response()->json([
                'message' => 'Access control rule deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('AccessControlController: Error deleting access rule', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to delete access control rule',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics about access control usage
     */
    public function getStats()
    {
        try {
            $totalRules = GroupPersonalizedLink::count();
            $activeRules = GroupPersonalizedLink::active()->count();
            $replacementRules = GroupPersonalizedLink::replacements()->count();
            $additionalRules = GroupPersonalizedLink::additions()->count();
            $groupsWithAccess = GroupPersonalizedLink::distinct('microsoft_group_name')->count('microsoft_group_name');

            return response()->json([
                'total_rules' => $totalRules,
                'active_rules' => $activeRules,
                'replacement_rules' => $replacementRules,
                'additional_rules' => $additionalRules,
                'groups_with_access' => $groupsWithAccess,
                'inactive_rules' => $totalRules - $activeRules
            ]);

        } catch (\Exception $e) {
            Log::error('AccessControlController: Error getting stats', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to get access control statistics',
                'message' => $e->getMessage()
            ], 500);
        }
    }
} 