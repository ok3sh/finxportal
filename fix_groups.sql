-- Fix Groups - Add rules for actual IT groups from Redis data
-- Based on user's actual groups: "IT team", "FinFinity IT", "SGPLDPT_IT"

-- Clear existing rules
DELETE FROM group_personalized_links;

-- Add rules for all IT-related groups the user actually belongs to
INSERT INTO group_personalized_links 
(microsoft_group_name, link_name, link_url, sort_order, replaces_link) 
VALUES 
('IT team', 'IT Tools', '/it-admin-tools', 1, 'outlook'),
('FinFinity IT', 'IT Tools', '/it-admin-tools', 1, 'outlook'),
('SGPLDPT_IT', 'IT Tools', '/it-admin-tools', 1, 'outlook');

-- Keep HR Admin rule
INSERT INTO group_personalized_links 
(microsoft_group_name, link_name, link_url, sort_order, replaces_link) 
VALUES 
('HR Admin', 'HR Tools', '/hr-admin-tools', 1, 'outlook');

-- Check results
SELECT 'Updated access control rules:' as info;
SELECT microsoft_group_name, link_name, link_url, replaces_link, is_active 
FROM group_personalized_links 
ORDER BY microsoft_group_name; 