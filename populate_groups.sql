-- Populate groups table with IT Admin and HR Admin
INSERT INTO groups (name, priority) VALUES 
('IT Admin', 1),
('HR Admin', 2)
ON DUPLICATE KEY UPDATE priority = VALUES(priority);

-- Clear existing access control data
DELETE FROM group_personalized_links;

-- Add IT Admin access control - replaces "outlook" with "IT Tools"
INSERT INTO group_personalized_links (
    microsoft_group_name, 
    link_name, 
    link_url, 
    link_logo, 
    background_color, 
    sort_order, 
    replaces_link
) VALUES (
    'IT Admin',
    'IT Tools', 
    '/it-admin-tools',  -- Internal portal route
    '/assets/it-tools.png',
    '#0066cc',
    1,
    'outlook'
);

-- Add HR Admin access control - replaces "outlook" with "HR Tools"  
INSERT INTO group_personalized_links (
    microsoft_group_name, 
    link_name, 
    link_url, 
    link_logo, 
    background_color, 
    sort_order, 
    replaces_link
) VALUES (
    'HR Admin',
    'HR Tools', 
    '/hr-admin-tools',  -- Internal portal route
    '/assets/hr-tools.png',
    '#059669',
    1,
    'outlook'
);

-- Verify the data
SELECT 'Groups Table:' as table_name;
SELECT * FROM groups ORDER BY priority;

SELECT 'Access Control Table:' as table_name;
SELECT * FROM group_personalized_links ORDER BY microsoft_group_name; 