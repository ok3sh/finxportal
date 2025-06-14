<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AssetController extends Controller
{
    /**
     * Get all assets with filtering options
     */
    public function index(Request $request)
    {
        try {
            // Check Microsoft Graph session authentication
            $user = Session::get('user');
            if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            $filter = $request->get('filter', 'all'); // all, active, inactive, decommissioned

            Log::info('AssetController: Fetching assets', ['filter' => $filter]);

            $query = DB::table('asset_master as am')
                ->leftJoin('allocated_asset_master as aam', function($join) {
                    $join->on('am.tag', '=', 'aam.asset_tag')
                         ->where('aam.status', 'active');
                })
                ->select(
                    'am.*',
                    'aam.user_email as allocated_to_email',
                    'aam.assign_on as allocated_on'
                );

            // Apply filters
            switch ($filter) {
                case 'active':
                    $query->where('am.status', 'active');
                    break;
                case 'inactive':
                    $query->where('am.status', 'inactive');
                    break;
                case 'decommissioned':
                    $query->where('am.status', 'decommissioned');
                    break;
                default:
                    // Return all assets
                    break;
            }

            $assets = $query->get()->map(function($asset) {
                return [
                    'id' => $asset->id,
                    'tag' => $asset->tag,
                    'type' => $asset->type,
                    'ownership' => $asset->ownership,
                    'warranty' => $asset->warranty,
                    'warranty_start' => $asset->warranty_start,
                    'warranty_end' => $asset->warranty_end,
                    'serial_number' => $asset->serial_number,
                    'model' => $asset->model,
                    'location' => $asset->location,
                    'status' => $asset->status,
                    'allocated_to_email' => $asset->allocated_to_email,
                    'allocated_on' => $asset->allocated_on,
                    'created_at' => $asset->created_at
                ];
            });

            return response()->json([
                'assets' => $assets,
                'total' => $assets->count(),
                'message' => 'Assets retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('AssetController: Error fetching assets', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'error' => 'Failed to fetch assets',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get asset types for dropdown
     */
    public function getAssetTypes()
    {
        try {
            $types = DB::table('asset_type_master')->get();
            return response()->json($types);
        } catch (\Exception $e) {
            Log::error('AssetController: Error fetching asset types', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch asset types'], 500);
        }
    }

    /**
     * Get locations for dropdown
     */
    public function getLocations()
    {
        try {
            $locations = DB::table('location_master')->get();
            return response()->json($locations);
        } catch (\Exception $e) {
            Log::error('AssetController: Error fetching locations', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch locations'], 500);
        }
    }

    /**
     * Create a new asset
     */
    public function store(Request $request)
    {
        try {
            // Check authentication
            $user = Session::get('user');
            if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            $request->validate([
                'type' => 'required|string|exists:asset_type_master,type',
                'ownership' => 'required|in:SGPL,Rental,BYOD',
                'warranty' => 'required|in:Under Warranty,NA,Out of Warranty',
                'warranty_start' => 'nullable|date',
                'warranty_end' => 'nullable|date|after:warranty_start',
                'serial_number' => 'required|string|max:30|unique:asset_master,serial_number',
                'model' => 'required|string|max:50',
                'location' => 'required|string|exists:location_master,unique_location'
            ]);

            // Generate asset tag
            $tag = $this->generateAssetTag($request->type, $request->ownership);

            DB::table('asset_master')->insert([
                'type' => $request->type,
                'ownership' => $request->ownership,
                'warranty' => $request->warranty,
                'warranty_start' => $request->warranty_start,
                'warranty_end' => $request->warranty_end,
                'serial_number' => $request->serial_number,
                'tag' => $tag,
                'model' => $request->model,
                'location' => $request->location,
                'status' => 'inactive',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info('AssetController: Created new asset', [
                'tag' => $tag,
                'type' => $request->type,
                'model' => $request->model
            ]);

            return response()->json([
                'message' => 'Asset created successfully',
                'tag' => $tag
            ], 201);

        } catch (\Exception $e) {
            Log::error('AssetController: Error creating asset', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to create asset',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Allocate asset to user
     */
    public function allocate(Request $request)
    {
        try {
            $user = Session::get('user');
            if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            $request->validate([
                'asset_tag' => 'required|string|exists:asset_master,tag',
                'user_email' => 'required|email'
            ]);

            // Check if asset is available (inactive)
            $asset = DB::table('asset_master')->where('tag', $request->asset_tag)->first();
            if (!$asset || $asset->status !== 'inactive') {
                return response()->json(['error' => 'Asset not available for allocation'], 400);
            }

            // Check if user already has this asset allocated
            $existingAllocation = DB::table('allocated_asset_master')
                ->where('asset_tag', $request->asset_tag)
                ->where('status', 'active')
                ->first();

            if ($existingAllocation) {
                return response()->json(['error' => 'Asset already allocated'], 400);
            }

            DB::beginTransaction();

            // Create allocation record
            DB::table('allocated_asset_master')->insert([
                'asset_tag' => $request->asset_tag,
                'user_email' => $request->user_email,
                'assign_on' => now(),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Update asset status to active
            DB::table('asset_master')
                ->where('tag', $request->asset_tag)
                ->update([
                    'status' => 'active',
                    'updated_at' => now()
                ]);

            DB::commit();

            Log::info('AssetController: Allocated asset', [
                'asset_tag' => $request->asset_tag,
                'user_email' => $request->user_email
            ]);

            return response()->json([
                'message' => 'Asset allocated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('AssetController: Error allocating asset', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to allocate asset',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deallocate asset
     */
    public function deallocate(Request $request)
    {
        try {
            $user = Session::get('user');
            if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            $request->validate([
                'asset_tag' => 'required|string|exists:asset_master,tag'
            ]);

            DB::beginTransaction();

            // Update allocation record to inactive
            DB::table('allocated_asset_master')
                ->where('asset_tag', $request->asset_tag)
                ->where('status', 'active')
                ->update([
                    'status' => 'inactive',
                    'end_date' => now(),
                    'updated_at' => now()
                ]);

            // Update asset status to inactive
            DB::table('asset_master')
                ->where('tag', $request->asset_tag)
                ->update([
                    'status' => 'inactive',
                    'updated_at' => now()
                ]);

            DB::commit();

            Log::info('AssetController: Deallocated asset', [
                'asset_tag' => $request->asset_tag
            ]);

            return response()->json([
                'message' => 'Asset deallocated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('AssetController: Error deallocating asset', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to deallocate asset',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reallocate asset to different user
     */
    public function reallocate(Request $request)
    {
        try {
            $user = Session::get('user');
            if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            $request->validate([
                'asset_tag' => 'required|string|exists:asset_master,tag',
                'new_user_email' => 'required|email'
            ]);

            DB::beginTransaction();

            // End current allocation
            DB::table('allocated_asset_master')
                ->where('asset_tag', $request->asset_tag)
                ->where('status', 'active')
                ->update([
                    'status' => 'inactive',
                    'end_date' => now(),
                    'updated_at' => now()
                ]);

            // Create new allocation
            DB::table('allocated_asset_master')->insert([
                'asset_tag' => $request->asset_tag,
                'user_email' => $request->new_user_email,
                'assign_on' => now(),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            Log::info('AssetController: Reallocated asset', [
                'asset_tag' => $request->asset_tag,
                'new_user_email' => $request->new_user_email
            ]);

            return response()->json([
                'message' => 'Asset reallocated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('AssetController: Error reallocating asset', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to reallocate asset',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Decommission assets
     */
    public function decommission(Request $request)
    {
        try {
            $user = Session::get('user');
            if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            $request->validate([
                'asset_tags' => 'required|array',
                'asset_tags.*' => 'required|string|exists:asset_master,tag'
            ]);

            DB::beginTransaction();

            foreach ($request->asset_tags as $assetTag) {
                // Check if asset is inactive
                $asset = DB::table('asset_master')->where('tag', $assetTag)->first();
                if ($asset->status !== 'inactive') {
                    DB::rollback();
                    return response()->json(['error' => "Asset {$assetTag} must be inactive before decommissioning"], 400);
                }

                // Update asset status to decommissioned
                DB::table('asset_master')
                    ->where('tag', $assetTag)
                    ->update([
                        'status' => 'decommissioned',
                        'updated_at' => now()
                    ]);
            }

            DB::commit();

            Log::info('AssetController: Decommissioned assets', [
                'asset_tags' => $request->asset_tags
            ]);

            return response()->json([
                'message' => 'Assets decommissioned successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('AssetController: Error decommissioning assets', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to decommission assets',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate asset tag
     */
    private function generateAssetTag($type, $ownership)
    {
        // Get prefix
        $prefix = ($ownership === 'SGPL') ? 'FIN' : 'EXT';
        
        // Get infix (keyword) from asset_type_master
        $typeData = DB::table('asset_type_master')->where('type', $type)->first();
        $infix = $typeData ? $typeData->keyword : 'UNK';
        
        // Get postfix (5-digit incremental number)
        $lastTag = DB::table('asset_master')
            ->where('tag', 'LIKE', $prefix . $infix . '%')
            ->orderBy('tag', 'desc')
            ->first();
        
        if ($lastTag) {
            // Extract postfix number and increment
            $lastPostfix = substr($lastTag->tag, -5);
            $newPostfix = str_pad((intval($lastPostfix) + 1), 5, '0', STR_PAD_LEFT);
        } else {
            // First asset of this type
            $newPostfix = '00001';
        }
        
        return $prefix . $infix . $newPostfix;
    }
} 