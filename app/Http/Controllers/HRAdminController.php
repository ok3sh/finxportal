<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\JobMaster;
use App\Models\CandidateMaster;
use App\Models\CandidateSourceMaster;
use App\Models\CandidateSkillMaster;
use App\Models\CandidateJob;
use App\Models\Interview;
use App\Services\EmailService;

class HRAdminController extends Controller
{
    private $emailService;

    public function __construct()
    {
        $this->emailService = new EmailService();
    }

    /**
     * 1. Create Job - Store a new job opening
     */
    public function createJob(Request $request)
    {
        try {
            // Check authentication
            $user = Session::get('user');
            if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            $request->validate([
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

            $job = JobMaster::create([
                'job_title' => $request->job_title,
                'department' => $request->department,
                'location' => $request->location,
                'hiring_manager' => $request->hiring_manager,
                'job_description' => $request->job_description,
                'experience_requirements' => $request->experience_requirements,
                'education_requirements' => $request->education_requirements,
                'number_of_openings' => $request->number_of_openings,
                'salary_min' => $request->salary_min,
                'salary_max' => $request->salary_max,
                'status' => 'Open'
            ]);

            Log::info('HRAdminController: Job created', [
                'job_id' => $job->id,
                'job_title' => $job->job_title
            ]);

            return response()->json([
                'message' => 'Job created successfully',
                'job' => $job
            ], 201);

        } catch (\Exception $e) {
            Log::error('HRAdminController: Error creating job', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to create job',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 2. Add Candidate - Store a new candidate
     */
    public function addCandidate(Request $request)
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
                'resume' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
                'notes' => 'nullable|string'
            ]);

            DB::beginTransaction();

            // Handle resume upload
            $resumePath = null;
            if ($request->hasFile('resume')) {
                $file = $request->file('resume');
                $resumePath = 'resumes/' . time() . '_' . $file->getClientOriginalName();
                Storage::put($resumePath, file_get_contents($file));
            }

            // Create candidate
            $candidate = CandidateMaster::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'source_id' => $request->source_id,
                'resume_path' => $resumePath,
                'notes' => $request->notes,
                'current_status' => 'New'
            ]);

            // Handle skills
            foreach ($request->skills as $skillName) {
                $skill = CandidateSkillMaster::firstOrCreate(['skill_name' => $skillName]);
                $candidate->skills()->attach($skill->id);
            }

            DB::commit();

            Log::info('HRAdminController: Candidate added', [
                'candidate_id' => $candidate->id,
                'name' => $candidate->name
            ]);

            return response()->json([
                'message' => 'Candidate added successfully',
                'candidate' => $candidate->load('skills', 'source')
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('HRAdminController: Error adding candidate', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to add candidate',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 3. Assign to Job - Link candidates to jobs
     */
    public function assignToJob(Request $request)
    {
        try {
            $user = Session::get('user');
            if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            $request->validate([
                'candidate_ids' => 'required|array',
                'candidate_ids.*' => 'required|integer|exists:candidates_master,id',
                'job_id' => 'required|integer|exists:jobs_master,id',
                'assignment_status' => 'required|in:Applied,Shortlisted',
                'email_content' => 'required_if:assignment_status,Applied|string'
            ]);

            DB::beginTransaction();

            $job = JobMaster::findOrFail($request->job_id);
            $assignedCandidates = [];

            foreach ($request->candidate_ids as $candidateId) {
                $candidate = CandidateMaster::findOrFail($candidateId);
                
                // Check if candidate is available for assignment
                if (!$candidate->isAvailableForAssignment()) {
                    continue;
                }

                // Check if already assigned to this job
                $existingAssignment = CandidateJob::where('candidate_id', $candidateId)
                    ->where('job_id', $request->job_id)
                    ->first();

                if ($existingAssignment) {
                    continue;
                }

                // Create assignment
                $assignment = CandidateJob::create([
                    'candidate_id' => $candidateId,
                    'job_id' => $request->job_id,
                    'assignment_status' => $request->assignment_status,
                    'assignment_notes' => $request->assignment_notes ?? null,
                    'assigned_at' => now()
                ]);

                // Update candidate status
                $candidate->update(['current_status' => $request->assignment_status]);

                $assignedCandidates[] = $candidate;

                // Send email if status is "Applied"
                if ($request->assignment_status === 'Applied') {
                    $this->sendCandidateApplicationEmail($candidate, $job, $request->email_content);
                }
            }

            DB::commit();

            Log::info('HRAdminController: Candidates assigned to job', [
                'job_id' => $request->job_id,
                'candidates_count' => count($assignedCandidates)
            ]);

            return response()->json([
                'message' => 'Candidates assigned successfully',
                'assigned_count' => count($assignedCandidates),
                'candidates' => $assignedCandidates
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('HRAdminController: Error assigning candidates', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to assign candidates',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 4. Candidate Approval - Verify applied candidates
     */
    public function approveCandidate(Request $request)
    {
        try {
            $user = Session::get('user');
            if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            $request->validate([
                'candidate_job_ids' => 'required|array',
                'candidate_job_ids.*' => 'required|integer|exists:candidate_jobs,id'
            ]);

            DB::beginTransaction();

            $verifiedAssignments = [];

            foreach ($request->candidate_job_ids as $assignmentId) {
                $assignment = CandidateJob::findOrFail($assignmentId);
                
                if ($assignment->canBeVerified()) {
                    $assignment->update(['assignment_status' => 'Verified']);
                    $assignment->candidate->update(['current_status' => 'Screening']);
                    $verifiedAssignments[] = $assignment->load('candidate', 'job');
                }
            }

            DB::commit();

            Log::info('HRAdminController: Candidates verified', [
                'verified_count' => count($verifiedAssignments)
            ]);

            return response()->json([
                'message' => 'Candidates verified successfully',
                'verified_count' => count($verifiedAssignments),
                'assignments' => $verifiedAssignments
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('HRAdminController: Error verifying candidates', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to verify candidates',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 5. Schedule Interview
     */
    public function scheduleInterview(Request $request)
    {
        try {
            $user = Session::get('user');
            if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            $userEmail = $user['profile']['userPrincipalName'] ?? $user['profile']['mail'];

            $request->validate([
                'candidate_id' => 'required|integer|exists:candidates_master,id',
                'job_id' => 'required|integer|exists:jobs_master,id',
                'interviewer_emails' => 'required|array',
                'interviewer_emails.*' => 'required|email',
                'interview_datetime' => 'required|date|after:now',
                'mode' => 'required|in:Video,In-person',
                'meeting_link_or_location' => 'required|string',
                'notes' => 'nullable|string'
            ]);

            DB::beginTransaction();

            $candidate = CandidateMaster::findOrFail($request->candidate_id);
            $job = JobMaster::findOrFail($request->job_id);

            // Create interview record
            $interview = Interview::create([
                'candidate_id' => $request->candidate_id,
                'job_id' => $request->job_id,
                'interviewer_emails' => $request->interviewer_emails,
                'interview_datetime' => $request->interview_datetime,
                'mode' => $request->mode,
                'meeting_link_or_location' => $request->meeting_link_or_location,
                'status' => 'Scheduled',
                'notes' => $request->notes,
                'created_by_email' => $userEmail
            ]);

            // Update candidate status
            $candidate->update(['current_status' => 'Interview']);

            // Update assignment status
            $assignment = CandidateJob::where('candidate_id', $request->candidate_id)
                ->where('job_id', $request->job_id)
                ->first();
            
            if ($assignment) {
                $assignment->update(['assignment_status' => 'Interviewing']);
            }

            DB::commit();

            // Send interview invitation email to candidate
            $this->sendInterviewInvitationEmail($candidate, $job, $interview);

            Log::info('HRAdminController: Interview scheduled', [
                'interview_id' => $interview->id,
                'candidate_name' => $candidate->name,
                'job_title' => $job->job_title
            ]);

            return response()->json([
                'message' => 'Interview scheduled successfully',
                'interview' => $interview->load('candidate', 'job')
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('HRAdminController: Error scheduling interview', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to schedule interview',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 6. Send Offer
     */
    public function sendOffer(Request $request)
    {
        try {
            $user = Session::get('user');
            if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            $userEmail = $user['profile']['userPrincipalName'] ?? $user['profile']['mail'];

            $request->validate([
                'candidate_id' => 'required|integer|exists:candidates_master,id',
                'job_id' => 'required|integer|exists:jobs_master,id',
                'offer_document' => 'required|file|mimes:pdf,doc,docx|max:10240',
                'subject_line' => 'required|string|max:255',
                'email_content' => 'required|string'
            ]);

            DB::beginTransaction();

            $candidate = CandidateMaster::findOrFail($request->candidate_id);
            $job = JobMaster::findOrFail($request->job_id);

            // Handle offer document upload
            $file = $request->file('offer_document');
            $offerPath = 'offers/' . time() . '_' . $file->getClientOriginalName();
            Storage::put($offerPath, file_get_contents($file));

            // Create offer record
            $offer = DB::table('offers')->insertGetId([
                'candidate_id' => $request->candidate_id,
                'job_id' => $request->job_id,
                'offer_document_path' => $offerPath,
                'subject_line' => $request->subject_line,
                'email_content' => $request->email_content,
                'status' => 'Sent',
                'sent_at' => now(),
                'created_by_email' => $userEmail,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Update candidate status
            $candidate->update(['current_status' => 'Offered']);

            // Update assignment status
            $assignment = CandidateJob::where('candidate_id', $request->candidate_id)
                ->where('job_id', $request->job_id)
                ->first();
            
            if ($assignment) {
                $assignment->update(['assignment_status' => 'Offered']);
            }

            DB::commit();

            // Send offer email with attachment
            $this->sendOfferEmail($candidate, $job, $request->subject_line, $request->email_content, $offerPath);

            Log::info('HRAdminController: Offer sent', [
                'offer_id' => $offer,
                'candidate_name' => $candidate->name,
                'job_title' => $job->job_title
            ]);

            return response()->json([
                'message' => 'Offer sent successfully',
                'offer_id' => $offer
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('HRAdminController: Error sending offer', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to send offer',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 7. Start Onboarding
     */
    public function startOnboarding(Request $request)
    {
        try {
            $user = Session::get('user');
            if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            $userEmail = $user['profile']['userPrincipalName'] ?? $user['profile']['mail'];

            $request->validate([
                'candidate_id' => 'required|integer|exists:candidates_master,id',
                'job_id' => 'required|integer|exists:jobs_master,id',
                'start_date' => 'required|date|after_or_equal:today',
                'manager_email' => 'required|email'
            ]);

            DB::beginTransaction();

            $candidate = CandidateMaster::findOrFail($request->candidate_id);
            $job = JobMaster::findOrFail($request->job_id);

            // Generate employee email
            $employeeEmail = $this->generateEmployeeEmail($candidate->name);

            // Create onboarding record
            $onboardingId = DB::table('onboarding')->insertGetId([
                'candidate_id' => $request->candidate_id,
                'job_id' => $request->job_id,
                'employee_email' => $employeeEmail,
                'start_date' => $request->start_date,
                'manager_email' => $request->manager_email,
                'status' => 'Initiated',
                'created_by_email' => $userEmail,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Create employee record
            $employeeId = DB::table('employees')->insertGetId([
                'name' => $candidate->name,
                'employee_email' => $employeeEmail,
                'personal_email' => $candidate->email,
                'phone' => $candidate->phone,
                'job_title' => $job->job_title,
                'department' => $job->department,
                'manager_email' => $request->manager_email,
                'start_date' => $request->start_date,
                'status' => 'Active',
                'onboarded_by_email' => $userEmail,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Update candidate status
            $candidate->update(['current_status' => 'Hired']);

            // Update assignment status
            $assignment = CandidateJob::where('candidate_id', $request->candidate_id)
                ->where('job_id', $request->job_id)
                ->first();
            
            if ($assignment) {
                $assignment->update(['assignment_status' => 'Hired']);
            }

            DB::commit();

            // Send welcome email to new employee
            $this->sendWelcomeEmail($candidate, $job, $employeeEmail, $request->manager_email, $request->start_date);

            // Notify IT Admin for asset handover
            $this->notifyITForAssetHandover($candidate, $job, $employeeEmail, $request->manager_email, $request->start_date);

            Log::info('HRAdminController: Onboarding started', [
                'onboarding_id' => $onboardingId,
                'employee_id' => $employeeId,
                'employee_email' => $employeeEmail
            ]);

            return response()->json([
                'message' => 'Onboarding started successfully',
                'employee_email' => $employeeEmail,
                'onboarding_id' => $onboardingId,
                'employee_id' => $employeeId
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('HRAdminController: Error starting onboarding', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to start onboarding',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 8. Mark Resignation
     */
    public function markResignation(Request $request)
    {
        try {
            $user = Session::get('user');
            if (!$user || !isset($user['authenticated']) || !$user['authenticated']) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            $request->validate([
                'employee_ids' => 'required|array',
                'employee_ids.*' => 'required|integer|exists:employees,id',
                'last_working_day' => 'required|date|after_or_equal:today',
                'resignation_reason' => 'nullable|string'
            ]);

            DB::beginTransaction();

            $resignedEmployees = [];

            foreach ($request->employee_ids as $employeeId) {
                $employee = DB::table('employees')->where('id', $employeeId)->first();
                
                if ($employee && $employee->status === 'Active') {
                    // Update employee status
                    DB::table('employees')->where('id', $employeeId)->update([
                        'status' => 'Resigned',
                        'last_working_day' => $request->last_working_day,
                        'resignation_reason' => $request->resignation_reason,
                        'resigned_at' => now(),
                        'updated_at' => now()
                    ]);

                    $resignedEmployees[] = $employee;

                    // Notify IT Admin for asset recovery
                    $this->notifyITForAssetRecovery($employee, $request->last_working_day, $request->resignation_reason);
                }
            }

            DB::commit();

            Log::info('HRAdminController: Employees marked as resigned', [
                'employee_count' => count($resignedEmployees)
            ]);

            return response()->json([
                'message' => 'Employees marked as resigned successfully',
                'resigned_count' => count($resignedEmployees)
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('HRAdminController: Error marking resignation', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to mark resignation',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Helper Methods for Email Automation

    private function sendCandidateApplicationEmail($candidate, $job, $emailContent)
    {
        try {
            $subject = "Application Received - {$job->job_title} Position";
            $htmlBody = "<p>Dear {$candidate->name},</p><p>Thank you for your application for the {$job->job_title} position.</p><p>{$emailContent}</p><p>Best regards,<br>HR Team</p>";
            
            $this->emailService->sendEmail(
                $candidate->email,
                $candidate->name,
                $subject,
                $htmlBody
            );
        } catch (\Exception $e) {
            Log::error('Failed to send candidate application email', ['error' => $e->getMessage()]);
        }
    }

    private function sendInterviewInvitationEmail($candidate, $job, $interview)
    {
        try {
            $subject = "Interview Invitation - {$job->job_title} Position";
            $htmlBody = "<p>Dear {$candidate->name},</p><p>We are pleased to invite you for an interview for the {$job->job_title} position.</p><p><strong>Interview Details:</strong><br>Date & Time: {$interview->interview_datetime->format('F j, Y \\a\\t g:i A')}<br>Mode: {$interview->mode}<br>Location/Link: {$interview->meeting_link_or_location}</p><p>Please confirm your attendance.</p><p>Best regards,<br>HR Team</p>";
            
            $this->emailService->sendEmail(
                $candidate->email,
                $candidate->name,
                $subject,
                $htmlBody
            );
        } catch (\Exception $e) {
            Log::error('Failed to send interview invitation email', ['error' => $e->getMessage()]);
        }
    }

    private function sendOfferEmail($candidate, $job, $subject, $emailContent, $offerPath)
    {
        try {
            $attachment = [
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name' => basename($offerPath),
                'contentType' => 'application/pdf',
                'contentBytes' => base64_encode(Storage::get($offerPath))
            ];

            $this->emailService->sendEmail(
                $candidate->email,
                $candidate->name,
                $subject,
                $emailContent,
                $attachment
            );
        } catch (\Exception $e) {
            Log::error('Failed to send offer email', ['error' => $e->getMessage()]);
        }
    }

    private function sendWelcomeEmail($candidate, $job, $employeeEmail, $managerEmail, $startDate)
    {
        try {
            $subject = "Welcome to FinFinity - Your First Day Information";
            $htmlBody = "<p>Dear {$candidate->name},</p><p>Welcome to FinFinity! We are excited to have you join our team as {$job->job_title}.</p><p><strong>Your Details:</strong><br>Start Date: {$startDate}<br>Employee Email: {$employeeEmail}<br>Manager: {$managerEmail}</p><p>Your IT assets will be prepared and you will receive further onboarding information shortly.</p><p>Best regards,<br>HR Team</p>";
            
            $this->emailService->sendEmail(
                $candidate->email,
                $candidate->name,
                $subject,
                $htmlBody
            );
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email', ['error' => $e->getMessage()]);
        }
    }

    private function notifyITForAssetHandover($candidate, $job, $employeeEmail, $managerEmail, $startDate)
    {
        try {
            $subject = "New Employee Asset Setup Required - {$candidate->name}";
            $htmlBody = "<p>Dear IT Team,</p><p>Please prepare assets for new employee:</p><p><strong>Employee Details:</strong><br>Name: {$candidate->name}<br>Email: {$employeeEmail}<br>Department: {$job->department}<br>Start Date: {$startDate}<br>Manager: {$managerEmail}</p><p>Please coordinate asset handover and email setup.</p><p>Best regards,<br>HR Team</p>";
            
            // Get IT admin emails - you may want to configure this
            $itAdminEmails = ['it@finfinity.co.in']; // Configure as needed
            
            foreach ($itAdminEmails as $itEmail) {
                $this->emailService->sendEmail(
                    $itEmail,
                    'IT Team',
                    $subject,
                    $htmlBody
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to send IT asset handover notification', ['error' => $e->getMessage()]);
        }
    }

    private function notifyITForAssetRecovery($employee, $lastWorkingDay, $resignationReason)
    {
        try {
            $subject = "Employee Resignation - Asset Recovery Required";
            $htmlBody = "<p>Dear IT Team,</p><p>Employee resignation notification:</p><p><strong>Employee Details:</strong><br>Name: {$employee->name}<br>Email: {$employee->employee_email}<br>Last Working Day: {$lastWorkingDay}<br>Reason: {$resignationReason}</p><p>Please coordinate asset recovery and email deactivation.</p><p>Best regards,<br>HR Team</p>";
            
            // Get IT admin emails
            $itAdminEmails = ['it@finfinity.co.in']; // Configure as needed
            
            foreach ($itAdminEmails as $itEmail) {
                $this->emailService->sendEmail(
                    $itEmail,
                    'IT Team',
                    $subject,
                    $htmlBody
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to send IT asset recovery notification', ['error' => $e->getMessage()]);
        }
    }

    private function generateEmployeeEmail($name)
    {
        // Convert name to email format
        $nameParts = explode(' ', strtolower(trim($name)));
        $baseEmail = implode('.', $nameParts);
        
        // Check if email already exists and increment number
        $counter = 1;
        $email = $baseEmail . sprintf('%03d', $counter) . '@finfinity.co.in';
        
        while (DB::table('employees')->where('employee_email', $email)->exists()) {
            $counter++;
            $email = $baseEmail . sprintf('%03d', $counter) . '@finfinity.co.in';
        }
        
        return $email;
    }

    // Data retrieval methods

    /**
     * Get all jobs
     */
    public function getJobs()
    {
        try {
            $jobs = JobMaster::with('candidateJobs.candidate')->get();
            return response()->json($jobs);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch jobs'], 500);
        }
    }

    /**
     * Get all candidates
     */
    public function getCandidates()
    {
        try {
            $candidates = CandidateMaster::with(['skills', 'source', 'candidateJobs.job'])->get();
            return response()->json($candidates);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch candidates'], 500);
        }
    }

    /**
     * Get candidate sources
     */
    public function getCandidateSources()
    {
        try {
            $sources = CandidateSourceMaster::all();
            return response()->json($sources);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch sources'], 500);
        }
    }

    /**
     * Get candidate skills
     */
    public function getCandidateSkills()
    {
        try {
            $skills = CandidateSkillMaster::all();
            return response()->json($skills);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch skills'], 500);
        }
    }

    /**
     * Get candidates available for assignment (not hired/rejected and not already assigned to the job)
     */
    public function getAvailableCandidates(Request $request)
    {
        try {
            $jobId = $request->query('job_id');
            
            $query = CandidateMaster::with(['skills', 'source'])
                ->notHired()
                ->whereNotIn('current_status', ['Rejected']);
            
            if ($jobId) {
                $query->whereDoesntHave('candidateJobs', function($q) use ($jobId) {
                    $q->where('job_id', $jobId);
                });
            }
            
            $candidates = $query->get();
            return response()->json($candidates);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch available candidates'], 500);
        }
    }

    /**
     * Get candidates awaiting approval (Applied status)
     */
    public function getCandidatesForApproval()
    {
        try {
            $assignments = CandidateJob::with(['candidate.skills', 'job'])
                ->applied()
                ->get();
            return response()->json($assignments);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch candidates for approval'], 500);
        }
    }

    /**
     * Get verified candidates for interview scheduling
     */
    public function getVerifiedCandidates()
    {
        try {
            $assignments = CandidateJob::with(['candidate.skills', 'job'])
                ->verified()
                ->get();
            return response()->json($assignments);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch verified candidates'], 500);
        }
    }

    /**
     * Get employees for resignation
     */
    public function getActiveEmployees()
    {
        try {
            $employees = DB::table('employees')
                ->where('status', 'Active')
                ->get();
            return response()->json($employees);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch active employees'], 500);
        }
    }
} 