-- Fix Access Control Data
-- Clear the corrupted data
DELETE FROM group_personalized_links;

-- Insert correct access control rules
-- Based on your actual Microsoft groups, adjust these group names as needed
INSERT INTO group_personalized_links (
    microsoft_group_name, 
    link_name, 
    link_url, 
    sort_order, 
    replaces_link,
    is_active
) VALUES 
-- IT Team group (replace "outlook" with "IT Tools")
('IT team', 'IT Tools', '/it-admin-tools', 1, 'outlook', true),

-- Add more groups as needed - adjust these group names to match your Azure AD groups exactly
('FinFinity IT', 'IT Tools', '/it-admin-tools', 1, 'outlook', true),
('SGPLDPT_IT', 'IT Tools', '/it-admin-tools', 1, 'outlook', true),
('HR Admin', 'HR Tools', '/hr-admin-tools', 1, 'outlook', true);

-- Verify the fixed data
SELECT 
    'Fixed Access Control Rules:' as status,
    microsoft_group_name,
    link_name,
    link_url,
    replaces_link,
    is_active
FROM group_personalized_links 
ORDER BY microsoft_group_name; 