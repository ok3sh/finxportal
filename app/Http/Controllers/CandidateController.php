<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CandidateController extends Controller
{
    /**
     * Get all candidates with optional filtering
     */
    public function index(Request $request)
    {
        try {
            $user = Session::get('user');
            if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            $query = DB::table('candidates_master as cm')
                ->join('candidate_source_master as csm', 'cm.source_id', '=', 'csm.id')
                ->select(
                    'cm.*',
                    'csm.source_name',
                    DB::raw('(SELECT GROUP_CONCAT(csm2.skill_name) 
                             FROM candidate_skills cs 
                             JOIN candidate_skill_master csm2 ON cs.skill_id = csm2.id 
                             WHERE cs.candidate_id = cm.id) as skills')
                );

            // Apply filters if provided
            if ($request->has('status')) {
                $query->where('cm.current_status', $request->status);
            }
            if ($request->has('source')) {
                $query->where('cm.source_id', $request->source);
            }
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('cm.name', 'LIKE', "%{$search}%")
                      ->orWhere('cm.email', 'LIKE', "%{$search}%")
                      ->orWhere('cm.phone', 'LIKE', "%{$search}%");
                });
            }

            $candidates = $query->get();

            return response()->json([
                'candidates' => $candidates,
                'total' => $candidates->count()
            ]);

        } catch (\Exception $e) {
            Log::error('CandidateController: Error fetching candidates', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch candidates',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get candidate sources for dropdown
     */
    public function getSources()
    {
        try {
            $sources = DB::table('candidate_source_master')->get();
            return response()->json($sources);
        } catch (\Exception $e) {
            Log::error('CandidateController: Error fetching sources', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to fetch sources'], 500);
        }
    }

    /**
     * Get existing skills for dropdown
     */
    public function getSkills()
    {
        try {
            $skills = DB::table('candidate_skill_master')->get();
            return response()->json($skills);
        } catch (\Exception $e) {
            Log::error('CandidateController: Error fetching skills', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to fetch skills'], 500);
        }
    }

    /**
     * Add a new candidate
     */
    public function store(Request $request)
    {
        try {
            $user = Session::get('user');
            if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            $request->validate([
                'name' => 'required|string|max:100',
                'email' => 'required|email|max:100|unique:candidates_master,email',
                'phone' => 'required|string|max:20',
                'source_id' => 'required|integer|exists:candidate_source_master,id',
                'skills' => 'required|array',
                'skills.*' => 'required|string|max:50',
                'resume' => 'nullable|file|mimes:pdf,doc,docx|max:5120', // 5MB max
                'notes' => 'nullable|string'
            ]);

            DB::beginTransaction();

            // Handle resume upload if provided
            $resumePath = null;
            if ($request->hasFile('resume')) {
                $file = $request->file('resume');
                $resumePath = 'resumes/' . time() . '_' . $file->getClientOriginalName();
                Storage::put($resumePath, file_get_contents($file));
            }

            // Insert candidate
            $candidateId = DB::table('candidates_master')->insertGetId([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'source_id' => $request->source_id,
                'resume_path' => $resumePath,
                'notes' => $request->notes,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Handle skills
            foreach ($request->skills as $skillName) {
                // Get or create skill
                $skillId = DB::table('candidate_skill_master')
                    ->where('skill_name', $skillName)
                    ->value('id');

                if (!$skillId) {
                    $skillId = DB::table('candidate_skill_master')->insertGetId([
                        'skill_name' => $skillName,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                // Map skill to candidate
                DB::table('candidate_skills')->insert([
                    'candidate_id' => $candidateId,
                    'skill_id' => $skillId,
                    'created_at' => now()
                ]);
            }

            DB::commit();

            Log::info('CandidateController: Created new candidate', [
                'id' => $candidateId,
                'name' => $request->name,
                'email' => $request->email
            ]);

            return response()->json([
                'message' => 'Candidate added successfully',
                'candidate_id' => $candidateId
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('CandidateController: Error creating candidate', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to add candidate',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update candidate status
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $user = Session::get('user');
            if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            $request->validate([
                'status' => 'required|in:New,Screening,Interview,Offered,Hired,Rejected'
            ]);

            DB::table('candidates_master')
                ->where('id', $id)
                ->update([
                    'current_status' => $request->status,
                    'updated_at' => now()
                ]);

            Log::info('CandidateController: Updated candidate status', [
                'id' => $id,
                'status' => $request->status
            ]);

            return response()->json([
                'message' => 'Candidate status updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('CandidateController: Error updating candidate status', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to update candidate status',
                'message' => $e->getMessage()
            ], 500);
        }
    }
} 