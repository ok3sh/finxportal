<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Jobs Master Table
        Schema::create('jobs_master', function (Blueprint $table) {
            $table->id();
            $table->string('job_title');
            $table->string('department');
            $table->string('location');
            $table->string('hiring_manager');
            $table->text('job_description');
            $table->text('experience_requirements')->nullable();
            $table->text('education_requirements')->nullable();
            $table->integer('number_of_openings')->default(1);
            $table->decimal('salary_min', 10, 2)->nullable();
            $table->decimal('salary_max', 10, 2)->nullable();
            $table->enum('status', ['Open', 'Closed', 'Hold'])->default('Open');
            $table->timestamps();
        });

        // Candidate Source Master Table
        Schema::create('candidate_source_master', function (Blueprint $table) {
            $table->id();
            $table->string('source_name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insert default sources
        DB::table('candidate_source_master')->insert([
            ['source_name' => 'LinkedIn', 'description' => 'LinkedIn platform'],
            ['source_name' => 'Referral', 'description' => 'Employee referral'],
            ['source_name' => 'Company Website', 'description' => 'Direct application'],
            ['source_name' => 'Job Portal', 'description' => 'Third-party job portals'],
            ['source_name' => 'Walk-in', 'description' => 'Walk-in candidate'],
            ['source_name' => 'Campus Hiring', 'description' => 'Campus recruitment'],
        ]);

        // Candidates Master Table
        Schema::create('candidates_master', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->unsignedBigInteger('source_id');
            $table->string('resume_path')->nullable();
            $table->text('notes')->nullable();
            $table->enum('current_status', ['New', 'Screening', 'Interview', 'Offered', 'Hired', 'Rejected'])->default('New');
            $table->timestamps();
            
            $table->foreign('source_id')->references('id')->on('candidate_source_master');
        });

        // Candidate Skills Master Table
        Schema::create('candidate_skill_master', function (Blueprint $table) {
            $table->id();
            $table->string('skill_name')->unique();
            $table->timestamps();
        });

        // Candidate Skills Junction Table
        Schema::create('candidate_skills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('skill_id');
            $table->timestamps();
            
            $table->foreign('candidate_id')->references('id')->on('candidates_master')->onDelete('cascade');
            $table->foreign('skill_id')->references('id')->on('candidate_skill_master');
            $table->unique(['candidate_id', 'skill_id']);
        });

        // Candidate Jobs Junction Table (Assignment)
        Schema::create('candidate_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('job_id');
            $table->enum('assignment_status', ['Applied', 'Shortlisted', 'Verified', 'Interviewing', 'Offered', 'Hired', 'Rejected'])->default('Applied');
            $table->text('assignment_notes')->nullable();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();
            
            $table->foreign('candidate_id')->references('id')->on('candidates_master')->onDelete('cascade');
            $table->foreign('job_id')->references('id')->on('jobs_master')->onDelete('cascade');
            $table->unique(['candidate_id', 'job_id']);
        });

        // Interviews Table
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('job_id');
            $table->json('interviewer_emails'); // Store array of interviewer emails
            $table->datetime('interview_datetime');
            $table->enum('mode', ['Video', 'In-person']);
            $table->text('meeting_link_or_location');
            $table->enum('status', ['Scheduled', 'Completed', 'Cancelled', 'Rescheduled'])->default('Scheduled');
            $table->text('notes')->nullable();
            $table->text('feedback')->nullable();
            $table->enum('result', ['Pass', 'Fail', 'On Hold', 'Pending'])->default('Pending');
            $table->string('created_by_email');
            $table->timestamps();
            
            $table->foreign('candidate_id')->references('id')->on('candidates_master')->onDelete('cascade');
            $table->foreign('job_id')->references('id')->on('jobs_master')->onDelete('cascade');
        });

        // Offers Table
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('job_id');
            $table->string('offer_document_path');
            $table->string('subject_line');
            $table->text('email_content');
            $table->datetime('sent_at')->nullable();
            $table->enum('status', ['Draft', 'Sent', 'Accepted', 'Rejected', 'Expired'])->default('Draft');
            $table->datetime('accepted_at')->nullable();
            $table->datetime('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('created_by_email');
            $table->timestamps();
            
            $table->foreign('candidate_id')->references('id')->on('candidates_master')->onDelete('cascade');
            $table->foreign('job_id')->references('id')->on('jobs_master')->onDelete('cascade');
        });

        // Onboarding Table
        Schema::create('onboarding', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('job_id');
            $table->string('employee_email')->unique(); // Auto-generated email
            $table->date('start_date');
            $table->string('manager_email');
            $table->enum('status', ['Initiated', 'IT_Notified', 'Assets_Assigned', 'Email_Provisioned', 'Completed'])->default('Initiated');
            $table->text('notes')->nullable();
            $table->json('it_assets')->nullable(); // Store assigned asset details
            $table->datetime('email_provisioned_at')->nullable();
            $table->string('created_by_email');
            $table->timestamps();
            
            $table->foreign('candidate_id')->references('id')->on('candidates_master')->onDelete('cascade');
            $table->foreign('job_id')->references('id')->on('jobs_master')->onDelete('cascade');
        });

        // Employees Table
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('employee_email')->unique();
            $table->string('personal_email')->nullable();
            $table->string('phone')->nullable();
            $table->string('job_title');
            $table->string('department');
            $table->string('manager_email')->nullable();
            $table->date('start_date');
            $table->enum('status', ['Active', 'Resigned', 'Terminated'])->default('Active');
            $table->date('last_working_day')->nullable();
            $table->text('resignation_reason')->nullable();
            $table->datetime('resigned_at')->nullable();
            $table->string('onboarded_by_email')->nullable();
            $table->timestamps();
        });

        // Email Templates Table
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_name')->unique();
            $table->string('subject');
            $table->text('body_html');
            $table->text('body_text');
            $table->enum('template_type', ['Candidate_Application', 'Interview_Invite', 'Offer_Letter', 'Welcome_Onboarding', 'IT_Asset_Request', 'Resignation_IT_Notification']);
            $table->json('variables')->nullable(); // Available template variables
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default email templates
        DB::table('email_templates')->insert([
            [
                'template_name' => 'Candidate Application Confirmation',
                'subject' => 'Application Received - {{job_title}} Position',
                'body_html' => '<p>Dear {{candidate_name}},</p><p>Thank you for your application for the {{job_title}} position. We have received your application and will review it shortly.</p><p>{{email_content}}</p><p>Best regards,<br>HR Team</p>',
                'body_text' => 'Dear {{candidate_name}}, Thank you for your application for the {{job_title}} position. {{email_content}} Best regards, HR Team',
                'template_type' => 'Candidate_Application',
                'variables' => json_encode(['candidate_name', 'job_title', 'email_content']),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'template_name' => 'Interview Invitation',
                'subject' => 'Interview Invitation - {{job_title}} Position',
                'body_html' => '<p>Dear {{candidate_name}},</p><p>We are pleased to invite you for an interview for the {{job_title}} position.</p><p><strong>Interview Details:</strong><br>Date & Time: {{interview_datetime}}<br>Mode: {{interview_mode}}<br>Location/Link: {{meeting_info}}</p><p>Please confirm your attendance.</p><p>Best regards,<br>HR Team</p>',
                'body_text' => 'Dear {{candidate_name}}, Interview invitation for {{job_title}} on {{interview_datetime}}. Mode: {{interview_mode}}. Location/Link: {{meeting_info}}. Please confirm attendance.',
                'template_type' => 'Interview_Invite',
                'variables' => json_encode(['candidate_name', 'job_title', 'interview_datetime', 'interview_mode', 'meeting_info']),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'template_name' => 'Welcome New Employee',
                'subject' => 'Welcome to {{company_name}} - Your First Day Information',
                'body_html' => '<p>Dear {{employee_name}},</p><p>Welcome to {{company_name}}! We are excited to have you join our team as {{job_title}}.</p><p><strong>Your Details:</strong><br>Start Date: {{start_date}}<br>Employee Email: {{employee_email}}<br>Manager: {{manager_name}}</p><p>Your IT assets will be prepared and you will receive further onboarding information shortly.</p><p>Best regards,<br>HR Team</p>',
                'body_text' => 'Welcome to the team! Your start date is {{start_date}} and your email is {{employee_email}}. Manager: {{manager_name}}.',
                'template_type' => 'Welcome_Onboarding',
                'variables' => json_encode(['employee_name', 'company_name', 'job_title', 'start_date', 'employee_email', 'manager_name']),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'template_name' => 'IT Asset Handover Request',
                'subject' => 'New Employee Asset Setup Required - {{employee_name}}',
                'body_html' => '<p>Dear IT Team,</p><p>Please prepare assets for new employee:</p><p><strong>Employee Details:</strong><br>Name: {{employee_name}}<br>Email: {{employee_email}}<br>Department: {{department}}<br>Start Date: {{start_date}}<br>Manager: {{manager_name}}</p><p>Please coordinate asset handover and email setup.</p><p>Best regards,<br>HR Team</p>',
                'body_text' => 'New employee {{employee_name}} ({{employee_email}}) starting {{start_date}}. Please prepare assets and email setup.',
                'template_type' => 'IT_Asset_Request',
                'variables' => json_encode(['employee_name', 'employee_email', 'department', 'start_date', 'manager_name']),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'template_name' => 'Employee Resignation - IT Notification',
                'subject' => 'Employee Resignation - Asset Recovery Required',
                'body_html' => '<p>Dear IT Team,</p><p>Employee resignation notification:</p><p><strong>Employee Details:</strong><br>Name: {{employee_name}}<br>Email: {{employee_email}}<br>Last Working Day: {{last_working_day}}<br>Reason: {{resignation_reason}}</p><p>Please coordinate asset recovery and email deactivation.</p><p>Best regards,<br>HR Team</p>',
                'body_text' => 'Employee {{employee_name}} ({{employee_email}}) last working day: {{last_working_day}}. Please recover assets and deactivate email.',
                'template_type' => 'Resignation_IT_Notification', 
                'variables' => json_encode(['employee_name', 'employee_email', 'last_working_day', 'resignation_reason']),
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('onboarding');
        Schema::dropIfExists('offers');
        Schema::dropIfExists('interviews');
        Schema::dropIfExists('candidate_jobs');
        Schema::dropIfExists('candidate_skills');
        Schema::dropIfExists('candidate_skill_master');
        Schema::dropIfExists('candidates_master');
        Schema::dropIfExists('candidate_source_master');
        Schema::dropIfExists('jobs_master');
    }
}; 