-- Step 8: Create Asset Management Tables

-- Step 9: Create HR Management Tables

-- Create jobs master table
CREATE TABLE IF NOT EXISTS jobs_master (
    id SERIAL PRIMARY KEY,
    job_title VARCHAR(255) NOT NULL,
    department VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    hiring_manager VARCHAR(255) NOT NULL,
    job_description TEXT NOT NULL,
    experience_requirements TEXT,
    education_requirements TEXT,
    number_of_openings INTEGER NOT NULL DEFAULT 1,
    salary_min DECIMAL(10,2),
    salary_max DECIMAL(10,2),
    status VARCHAR(20) DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create candidate source master table
CREATE TABLE IF NOT EXISTS candidate_source_master (
    id SERIAL PRIMARY KEY,
    source_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default candidate sources
INSERT INTO candidate_source_master (source_name, description) VALUES
('LinkedIn', 'Professional networking platform'),
('Company Website', 'Direct applications through company career page'),
('Referral', 'Employee referrals'),
('Job Board', 'Third-party job posting websites'),
('Recruitment Agency', 'External recruitment partners'),
('Walk-in', 'Direct walk-in applications'),
('Campus Hiring', 'University and college recruitment')
ON CONFLICT (source_name) DO NOTHING;

-- Create candidate skill master table
CREATE TABLE IF NOT EXISTS candidate_skill_master (
    id SERIAL PRIMARY KEY,
    skill_name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert common skills
INSERT INTO candidate_skill_master (skill_name) VALUES
('JavaScript'), ('React'), ('Node.js'), ('PHP'), ('Laravel'), ('Python'), ('Java'),
('SQL'), ('PostgreSQL'), ('MySQL'), ('MongoDB'), ('Git'), ('Docker'), ('AWS'),
('Project Management'), ('Communication'), ('Leadership'), ('Problem Solving')
ON CONFLICT (skill_name) DO NOTHING;

-- Create candidates master table
CREATE TABLE IF NOT EXISTS candidates_master (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    source_id INTEGER NOT NULL,
    resume_path VARCHAR(500),
    notes TEXT,
    current_status VARCHAR(50) DEFAULT 'New',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (source_id) REFERENCES candidate_source_master(id) ON DELETE RESTRICT
);

-- Create candidate skills junction table
CREATE TABLE IF NOT EXISTS candidate_skills (
    id SERIAL PRIMARY KEY,
    candidate_id INTEGER NOT NULL,
    skill_id INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (candidate_id) REFERENCES candidates_master(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES candidate_skill_master(id) ON DELETE CASCADE,
    UNIQUE(candidate_id, skill_id)
);

-- Create candidate jobs assignment table
CREATE TABLE IF NOT EXISTS candidate_jobs (
    id SERIAL PRIMARY KEY,
    candidate_id INTEGER NOT NULL,
    job_id INTEGER NOT NULL,
    assignment_status VARCHAR(50) DEFAULT 'Applied',
    assignment_notes TEXT,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (candidate_id) REFERENCES candidates_master(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs_master(id) ON DELETE CASCADE,
    UNIQUE(candidate_id, job_id)
);

-- Create interviews table
CREATE TABLE IF NOT EXISTS interviews (
    id SERIAL PRIMARY KEY,
    candidate_id INTEGER NOT NULL,
    job_id INTEGER NOT NULL,
    interviewer_emails JSON NOT NULL,
    interview_datetime TIMESTAMP NOT NULL,
    mode VARCHAR(20) NOT NULL CHECK (mode IN ('Video', 'In-person')),
    meeting_link_or_location TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'Scheduled',
    notes TEXT,
    feedback TEXT,
    result VARCHAR(20),
    created_by_email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (candidate_id) REFERENCES candidates_master(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs_master(id) ON DELETE CASCADE
);

-- Create offers table
CREATE TABLE IF NOT EXISTS offers (
    id SERIAL PRIMARY KEY,
    candidate_id INTEGER NOT NULL,
    job_id INTEGER NOT NULL,
    offer_document_path VARCHAR(500) NOT NULL,
    subject_line VARCHAR(255) NOT NULL,
    email_content TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'Sent',
    sent_at TIMESTAMP,
    accepted_at TIMESTAMP,
    declined_at TIMESTAMP,
    created_by_email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (candidate_id) REFERENCES candidates_master(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs_master(id) ON DELETE CASCADE
);

-- Create onboarding table
CREATE TABLE IF NOT EXISTS onboarding (
    id SERIAL PRIMARY KEY,
    candidate_id INTEGER NOT NULL,
    job_id INTEGER NOT NULL,
    employee_email VARCHAR(255) NOT NULL UNIQUE,
    start_date DATE NOT NULL,
    manager_email VARCHAR(255) NOT NULL,
    status VARCHAR(20) DEFAULT 'Initiated',
    completed_at TIMESTAMP,
    created_by_email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (candidate_id) REFERENCES candidates_master(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs_master(id) ON DELETE CASCADE
);

-- Create employees table
CREATE TABLE IF NOT EXISTS employees (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    employee_email VARCHAR(255) NOT NULL UNIQUE,
    personal_email VARCHAR(100),
    phone VARCHAR(20),
    job_title VARCHAR(255),
    department VARCHAR(255),
    manager_email VARCHAR(255),
    start_date DATE,
    last_working_day DATE,
    status VARCHAR(20) DEFAULT 'Active',
    resignation_reason TEXT,
    resigned_at TIMESTAMP,
    onboarded_by_email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create email templates table
CREATE TABLE IF NOT EXISTS email_templates (
    id SERIAL PRIMARY KEY,
    template_name VARCHAR(100) NOT NULL UNIQUE,
    subject_line VARCHAR(255) NOT NULL,
    email_content TEXT NOT NULL,
    template_type VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default email templates
INSERT INTO email_templates (template_name, subject_line, email_content, template_type) VALUES
('candidate_application', 'Application Received - {{job_title}} Position', 
 '<p>Dear {{candidate_name}},</p><p>Thank you for your application for the {{job_title}} position. We have received your application and will review it shortly.</p><p>Best regards,<br>HR Team</p>', 
 'candidate_communication'),
('interview_invitation', 'Interview Invitation - {{job_title}} Position',
 '<p>Dear {{candidate_name}},</p><p>We are pleased to invite you for an interview for the {{job_title}} position.</p><p><strong>Interview Details:</strong><br>Date & Time: {{interview_datetime}}<br>Mode: {{interview_mode}}<br>Location/Link: {{meeting_details}}</p><p>Please confirm your attendance.</p><p>Best regards,<br>HR Team</p>',
 'candidate_communication'),
('welcome_email', 'Welcome to FinFinity - Your First Day Information',
 '<p>Dear {{employee_name}},</p><p>Welcome to FinFinity! We are excited to have you join our team as {{job_title}}.</p><p><strong>Your Details:</strong><br>Start Date: {{start_date}}<br>Employee Email: {{employee_email}}<br>Manager: {{manager_email}}</p><p>Your IT assets will be prepared and you will receive further onboarding information shortly.</p><p>Best regards,<br>HR Team</p>',
 'employee_communication')
ON CONFLICT (template_name) DO NOTHING;

-- Create Asset Type Master table
CREATE TABLE IF NOT EXISTS asset_type_master (
    id SERIAL PRIMARY KEY,
    type VARCHAR(50) NOT NULL UNIQUE,
    keyword VARCHAR(10) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert asset types
INSERT INTO asset_type_master (type, keyword) VALUES
('Laptop', 'LAP'),
('Desktop', 'DSK'),
('Mouse', 'MOU'),
('Keyboard', 'KEY'),
('Mobile', 'MOB')
ON CONFLICT (type) DO NOTHING;

-- Create Location Master table
CREATE TABLE IF NOT EXISTS location_master (
    id SERIAL PRIMARY KEY,
    unique_location VARCHAR(255) NOT NULL UNIQUE,
    total_assets INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample locations
INSERT INTO location_master (unique_location, total_assets) VALUES
('Office Floor 1', 0),
('Office Floor 2', 0),
('Office Floor 3', 0),
('Remote Work', 0),
('Conference Room A', 0),
('Conference Room B', 0),
('Storage Room', 0),
('IT Department', 0)
ON CONFLICT (unique_location) DO NOTHING;

-- Create ownership enum type
DO $$ BEGIN
    CREATE TYPE ownership_type AS ENUM ('SGPL', 'Rental', 'BYOD');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

-- Create warranty enum type
DO $$ BEGIN
    CREATE TYPE warranty_type AS ENUM ('Under Warranty', 'NA', 'Out of Warranty');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

-- Create asset status enum type
DO $$ BEGIN
    CREATE TYPE asset_status_type AS ENUM ('active', 'inactive', 'decommissioned');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

-- Create allocation status enum type
DO $$ BEGIN
    CREATE TYPE allocation_status_type AS ENUM ('active', 'inactive');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

-- Create Asset Master table
CREATE TABLE IF NOT EXISTS asset_master (
    id SERIAL PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    ownership ownership_type NOT NULL,
    warranty warranty_type NOT NULL,
    warranty_start DATE NULL,
    warranty_end DATE NULL,
    serial_number VARCHAR(30) NOT NULL UNIQUE,
    tag VARCHAR(20) NOT NULL UNIQUE,
    model VARCHAR(50) NOT NULL,
    location VARCHAR(255) NOT NULL,
    status asset_status_type DEFAULT 'inactive',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (type) REFERENCES asset_type_master(type) ON UPDATE CASCADE,
    FOREIGN KEY (location) REFERENCES location_master(unique_location) ON UPDATE CASCADE
);

-- Create Allocated Asset Master table
CREATE TABLE IF NOT EXISTS allocated_asset_master (
    id SERIAL PRIMARY KEY,
    asset_tag VARCHAR(20) NOT NULL,
    user_email VARCHAR(255) NOT NULL,
    assign_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status allocation_status_type DEFAULT 'active',
    end_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_tag) REFERENCES asset_master(tag) ON UPDATE CASCADE ON DELETE CASCADE
);

-- Insert sample assets
INSERT INTO asset_master (type, ownership, warranty, warranty_start, warranty_end, serial_number, tag, model, location, status) VALUES
('Laptop', 'SGPL', 'Under Warranty', '2024-01-01', '2026-01-01', 'DL123456789', 'FINLAP00001', 'Dell Latitude 5520', 'Office Floor 1', 'inactive'),
('Laptop', 'SGPL', 'Under Warranty', '2024-02-01', '2026-02-01', 'HP987654321', 'FINLAP00002', 'HP EliteBook 840', 'Office Floor 2', 'active'),
('Desktop', 'SGPL', 'Under Warranty', '2024-01-15', '2026-01-15', 'DP111222333', 'FINDSK00001', 'Dell OptiPlex 7090', 'Office Floor 1', 'inactive'),
('Mobile', 'Rental', 'NA', NULL, NULL, 'IP555666777', 'EXTMOB00001', 'iPhone 14 Pro', 'Remote Work', 'active'),
('Mouse', 'SGPL', 'Out of Warranty', NULL, NULL, 'LM888999000', 'FINMOU00001', 'Logitech MX Master 3', 'Office Floor 1', 'inactive'),
('Keyboard', 'SGPL', 'Under Warranty', '2024-03-01', '2025-03-01', 'LK111333555', 'FINKEY00001', 'Logitech K380', 'Office Floor 2', 'inactive'),
('Laptop', 'BYOD', 'NA', NULL, NULL, 'MB777888999', 'EXTLAP00001', 'MacBook Pro M2', 'Remote Work', 'active'),
('Desktop', 'SGPL', 'Under Warranty', '2024-04-01', '2026-04-01', 'DP444555666', 'FINDSK00002', 'HP EliteDesk 800', 'IT Department', 'inactive')
ON CONFLICT (serial_number) DO NOTHING;

-- Insert sample allocations
INSERT INTO allocated_asset_master (asset_tag, user_email, assign_on, status, end_date) VALUES
('FINLAP00002', 'john.doe@company.com', '2024-01-15 09:00:00', 'active', NULL),
('EXTMOB00001', 'jane.smith@company.com', '2024-02-01 10:30:00', 'active', NULL),
('EXTLAP00001', 'mike.wilson@company.com', '2024-03-01 11:00:00', 'active', NULL)
ON CONFLICT DO NOTHING;

-- Update asset status for allocated items
UPDATE asset_master SET status = 'active' WHERE tag IN ('FINLAP00002', 'EXTMOB00001', 'EXTLAP00001');

-- Drop old tables if they exist
DROP TABLE IF EXISTS all_asset_table CASCADE;
DROP TABLE IF EXISTS asset_requests CASCADE;

-- Step 10: Check results
SELECT 'Groups Table:' as result_type;
SELECT * FROM groups WHERE name IN ('IT team', 'FinFinity IT', 'SGPLDPT_IT', 'SGPL ALL USERS');

SELECT 'Access Control Table:' as result_type;
SELECT microsoft_group_name, link_name, link_url, replaces_link, is_active
FROM group_personalized_links 
ORDER BY microsoft_group_name; 

SELECT 'Asset Statistics:' as result_type;
SELECT 
    COUNT(*) as total_assets,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_assets,
    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_assets,
    COUNT(CASE WHEN status = 'decommissioned' THEN 1 END) as decommissioned_assets
FROM asset_master;

SELECT 'HR Management Statistics:' as result_type;
SELECT 
    (SELECT COUNT(*) FROM jobs_master) as total_jobs,
    (SELECT COUNT(*) FROM jobs_master WHERE status = 'Open') as open_jobs,
    (SELECT COUNT(*) FROM candidates_master) as total_candidates,
    (SELECT COUNT(*) FROM candidate_jobs) as total_assignments,
    (SELECT COUNT(*) FROM interviews) as total_interviews,
    (SELECT COUNT(*) FROM offers) as total_offers,
    (SELECT COUNT(*) FROM employees WHERE status = 'Active') as active_employees;

SELECT 'Candidate Sources:' as result_type;
SELECT id, source_name, description FROM candidate_source_master ORDER BY id;

SELECT 'Sample Jobs:' as result_type;
SELECT id, job_title, department, location, status, number_of_openings 
FROM jobs_master 
ORDER BY created_at DESC 
LIMIT 5; 