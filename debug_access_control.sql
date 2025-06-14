-- Debug Access Control System
-- Run this to see what data exists and debug issues

-- Check if the table exists and what data is in it
SELECT 'group_personalized_links table contents:' as debug_info;
SELECT 
    id,
    microsoft_group_name,
    link_name,
    link_url,
    replaces_link,
    is_active,
    created_at
FROM group_personalized_links 
ORDER BY microsoft_group_name, sort_order;

-- Check groups table
SELECT 'groups table contents:' as debug_info;
SELECT * FROM groups ORDER BY priority;

-- Check what we expect vs what exists
SELECT 'Expected IT Admin group replacement:' as debug_info;
SELECT COUNT(*) as count_found,
       microsoft_group_name,
       link_name,
       replaces_link
FROM group_personalized_links 
WHERE microsoft_group_name = 'IT Admin' 
  AND replaces_link = 'outlook'
  AND is_active = true
GROUP BY microsoft_group_name, link_name, replaces_link;

-- Check for case-sensitive issues
SELECT 'Check for case sensitivity issues:' as debug_info;
SELECT microsoft_group_name, replaces_link
FROM group_personalized_links
WHERE LOWER(replaces_link) = 'outlook';

-- Show all active replacements
SELECT 'All active replacement rules:' as debug_info;
SELECT microsoft_group_name, link_name, replaces_link, is_active
FROM group_personalized_links
WHERE replaces_link IS NOT NULL
ORDER BY microsoft_group_name; 