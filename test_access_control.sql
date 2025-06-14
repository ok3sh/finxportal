-- Quick Test Script for Access Control System
-- Run this after creating the main access control table

-- 1. Check if the table was created successfully
SELECT COUNT(*) as total_rules FROM group_personalized_links;

-- 2. View all sample data
SELECT 
    microsoft_group_name,
    link_name,
    link_url,
    replaces_link,
    CASE 
        WHEN replaces_link IS NULL THEN 'Additional Link'
        ELSE CONCAT('Replaces: ', replaces_link)
    END as link_type,
    is_active
FROM group_personalized_links 
ORDER BY microsoft_group_name, sort_order;

-- 3. Test data for common groups (adjust group names to match your Azure AD)
-- Replace 'IT Support' with your actual IT group name from Azure AD

-- Add test rule for your IT group
INSERT IGNORE INTO group_personalized_links (
    microsoft_group_name, 
    link_name, 
    link_url, 
    link_logo, 
    background_color, 
    sort_order, 
    replaces_link
) VALUES (
    'IT Support',  -- Change this to your actual IT group name
    'Test Asset Management', 
    'https://test-asset-management.company.com',
    '/assets/test-asset.png',
    '#0066cc',
    1,
    'outlook'  -- This will replace the "outlook" link
);

-- Add test additional link
INSERT IGNORE INTO group_personalized_links (
    microsoft_group_name, 
    link_name, 
    link_url, 
    link_logo, 
    background_color, 
    sort_order, 
    replaces_link
) VALUES (
    'IT Support',  -- Change this to your actual IT group name
    'Test Monitoring', 
    'https://test-monitoring.company.com',
    '/assets/test-monitoring.png',
    '#28a745',
    2,
    NULL  -- Additional link, doesn't replace anything
);

-- 4. View the test data you just added
SELECT 
    microsoft_group_name,
    link_name,
    link_url,
    replaces_link,
    is_active
FROM group_personalized_links 
WHERE microsoft_group_name = 'IT Support'  -- Change to your group name
ORDER BY sort_order;

-- 5. Quick cleanup (run this if you want to remove test data)
-- DELETE FROM group_personalized_links WHERE link_name LIKE 'Test%';

-- 6. Check group statistics
SELECT 
    microsoft_group_name,
    COUNT(*) as total_links,
    SUM(CASE WHEN replaces_link IS NOT NULL THEN 1 ELSE 0 END) as replacement_links,
    SUM(CASE WHEN replaces_link IS NULL THEN 1 ELSE 0 END) as additional_links
FROM group_personalized_links 
WHERE is_active = TRUE
GROUP BY microsoft_group_name
ORDER BY microsoft_group_name; 