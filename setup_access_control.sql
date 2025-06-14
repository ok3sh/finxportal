-- Setup Access Control for RightPanel Text Replacement
-- This only affects the RightPanel.jsx "Outlook" text, not the main dashboard links

-- Create the access control table if it doesn't exist
CREATE TABLE IF NOT EXISTS group_personalized_links (
    id INT PRIMARY KEY AUTO_INCREMENT,
    microsoft_group_name VARCHAR(255) NOT NULL,
    link_name VARCHAR(255) NOT NULL,
    link_url VARCHAR(500) NOT NULL,
    link_logo VARCHAR(255) NULL,
    background_color VARCHAR(10) DEFAULT '#115948',
    sort_order INT DEFAULT 1,
    replaces_link VARCHAR(255) NULL COMMENT 'Which default link this replaces (e.g., "outlook")',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_group_replaces (microsoft_group_name, replaces_link)
);

-- Populate groups table
INSERT INTO groups (name, priority) VALUES 
('IT Admin', 1),
('HR Admin', 2)
ON DUPLICATE KEY UPDATE priority = VALUES(priority);

-- Clear existing access control data
DELETE FROM group_personalized_links;

-- IT Admin: Replace "Outlook" text with "IT Tools" in RightPanel
INSERT INTO group_personalized_links (
    microsoft_group_name, 
    link_name, 
    link_url, 
    sort_order, 
    replaces_link
) VALUES (
    'IT Admin',
    'IT Tools', 
    '/it-admin-tools',
    1,
    'outlook'
);

-- HR Admin: Replace "Outlook" text with "HR Tools" in RightPanel  
INSERT INTO group_personalized_links (
    microsoft_group_name, 
    link_name, 
    link_url, 
    sort_order, 
    replaces_link
) VALUES (
    'HR Admin',
    'HR Tools', 
    '/hr-admin-tools',
    1,
    'outlook'
);

-- Verify the setup
SELECT 'Groups:' as info;
SELECT * FROM groups ORDER BY priority;

SELECT 'Access Control:' as info;
SELECT microsoft_group_name, link_name, link_url, replaces_link FROM group_personalized_links; 