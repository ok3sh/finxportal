-- Fix the access control groups to match user's actual Microsoft groups
-- Based on Redis data: "IT team", "FinFinity IT", "SGPLDPT_IT"

-- Delete the wrong entries
DELETE FROM group_personalized_links WHERE microsoft_group_name IN ('SGPLDPT_IT', 'IT team\');

-- Add correct group names for all IT groups
INSERT INTO group_personalized_links (
    microsoft_group_name, 
    link_name, 
    link_url, 
    background_color, 
    sort_order, 
    replaces_link, 
    is_active
) VALUES 
-- IT team group
('IT team', 'IT Tools', '/it-admin-tools', '#115948', 1, 'outlook', true),

-- FinFinity IT group  
('FinFinity IT', 'IT Tools', '/it-admin-tools', '#115948', 1, 'outlook', true),

-- SGPLDPT_IT group
('SGPLDPT_IT', 'IT Tools', '/it-admin-tools', '#115948', 1, 'outlook', true)

ON CONFLICT (microsoft_group_name, replaces_link) DO NOTHING;

-- Verify the changes
SELECT 'Fixed Access Control Rules:' as result_type;
SELECT 
    microsoft_group_name, 
    link_name, 
    link_url, 
    replaces_link,
    is_active
FROM group_personalized_links 
WHERE is_active = true
ORDER BY microsoft_group_name;