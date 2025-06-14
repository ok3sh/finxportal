<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobController extends Controller
{
    /**
     * Store a new job opening in jobs_master
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'job_title' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'hiring_manager' => 'required|string|max:255',
            'job_description' => 'required|string',
            'experience_requirements' => 'nullable|string',
            'education_requirements' => 'nullable|string',
            'number_of_openings' => 'required|integer|min:1',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::table('jobs_master')->insert([
                'job_title' => $validated['job_title'],
                'department' => $validated['department'],
                'location' => $validated['location'],
                'hiring_manager' => $validated['hiring_manager'],
                'job_description' => $validated['job_description'],
                'experience_requirements' => $validated['experience_requirements'] ?? null,
                'education_requirements' => $validated['education_requirements'] ?? null,
                'number_of_openings' => $validated['number_of_openings'],
                'salary_min' => $validated['salary_min'] ?? 0,
                'salary_max' => $validated['salary_max'] ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return response()->json(['message' => 'Job created successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create job',
                'details' => $e->getMessage()
            ], 500);
        }
    }
} 