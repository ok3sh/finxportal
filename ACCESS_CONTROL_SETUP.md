# Access Control System for Personalized Links

## Overview
The access control system allows you to show different links to users based on their Microsoft Group membership. For example:
- **IT Admin** group sees "Asset Management" instead of "Outlook"
- **HR Admin** group sees "HR Management" instead of "Outlook"
- **Finance Team** gets additional "Budget Tracker" link
- And so on...

## Database Setup

### 1. Check Current Database Structure
Run the SQL commands from `check_db_structure.sql`:
```sql
-- This will show you all existing tables and their structure
SHOW TABLES;
DESCRIBE links;  -- Your current links table
```

### 2. Create Access Control Table
Run the SQL commands from `create_access_control.sql`:
```sql
-- Creates the new group_personalized_links table
-- Includes sample data for IT Admin, HR Admin, etc.
```

## How It Works

### Link Types
1. **Replacement Links**: Replace existing default links
   - Example: "Asset Management" replaces "Outlook" for IT Admin
   - Uses `replaces_link` field to specify which link to replace

2. **Additional Links**: Add new links without replacing anything
   - Example: "Server Monitoring" as an extra link for IT Admin
   - `replaces_link` field is NULL

### User Flow
```javascript
1. User logs in via Microsoft Graph
2. System gets user's group memberships 
3. LinkController checks for personalized links for user's groups
4. Applies replacements and adds additional links
5. Returns personalized link list to frontend
```

## API Endpoints

### Get Personalized Links (Existing endpoint, now enhanced)
```
GET /api/links
```
Returns personalized links based on user's Microsoft groups.

### Debug User's Access
```
GET /api/links/access-info
```
Shows what groups user belongs to and what personalized links are available.

### Admin: View All Access Rules
```
GET /api/admin/access-control
```
Returns all configured access control rules grouped by Microsoft group.

### Admin: Create New Access Rule
```
POST /api/admin/access-control
Content-Type: application/json

{
  "microsoft_group_name": "IT Admin",
  "link_name": "Asset Management",
  "link_url": "https://asset-management.company.com",
  "link_logo": "/assets/asset-management.png",
  "background_color": "#2563eb",
  "sort_order": 1,
  "replaces_link": "outlook"  // Optional: which link to replace
}
```

### Admin: Update Access Rule
```
PUT /api/admin/access-control/{id}
Content-Type: application/json

{
  "link_name": "Updated Asset Management",
  "link_url": "https://new-asset-management.company.com",
  "is_active": true
}
```

### Admin: Delete Access Rule
```
DELETE /api/admin/access-control/{id}
```

### Admin: Get Statistics
```
GET /api/admin/access-control/stats
```
Returns statistics about access control usage.

## Configuration Examples

### Example 1: Replace "Outlook" with "Asset Management" for IT Admin
```sql
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
    'Asset Management', 
    'https://asset-management.company.com',
    '/assets/asset-management.png',
    '#2563eb',
    1,
    'outlook'
);
```

### Example 2: Add Additional Link for IT Admin
```sql
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
    'Server Monitoring', 
    'https://monitoring.company.com',
    '/assets/monitoring.png',
    '#1f2937',
    2,
    NULL  -- No replacement, this is an additional link
);
```

### Example 3: Multiple Groups, Same Replacement
```sql
-- Replace "Outlook" with different systems for different teams
INSERT INTO group_personalized_links (microsoft_group_name, link_name, link_url, replaces_link) VALUES
('HR Admin', 'HR Management', 'https://hr-system.company.com', 'outlook'),
('Finance Team', 'Financial Dashboard', 'https://finance.company.com', 'outlook'),
('Marketing Team', 'Campaign Manager', 'https://campaigns.company.com', 'outlook');
```

## Testing the System

### 1. Check Your Groups
First, verify what Microsoft groups you belong to:
```bash
curl -X GET "https://your-domain/api/links/access-info" \
  -H "Cookie: your-session-cookie"
```

### 2. View Current Links
See what links are currently shown to you:
```bash
curl -X GET "https://your-domain/api/links" \
  -H "Cookie: your-session-cookie"
```

### 3. Add Test Access Rule
Create a test rule for your group:
```bash
curl -X POST "https://your-domain/api/admin/access-control" \
  -H "Content-Type: application/json" \
  -H "Cookie: your-session-cookie" \
  -d '{
    "microsoft_group_name": "Your Group Name",
    "link_name": "Test System",
    "link_url": "https://test-system.company.com",
    "link_logo": "/assets/test.png",
    "background_color": "#ff6b6b",
    "sort_order": 1,
    "replaces_link": "outlook"
  }'
```

### 4. Verify Changes
Check that your links changed:
```bash
curl -X GET "https://your-domain/api/links" \
  -H "Cookie: your-session-cookie"
```

## Database Schema

### group_personalized_links Table
```sql
CREATE TABLE group_personalized_links (
    id INT PRIMARY KEY AUTO_INCREMENT,
    microsoft_group_name VARCHAR(255) NOT NULL,     -- Azure AD group name
    link_name VARCHAR(255) NOT NULL,                -- Display name for link
    link_url VARCHAR(500) NOT NULL,                 -- Target URL
    link_logo VARCHAR(255) NULL,                    -- Logo path or URL
    background_color VARCHAR(10) DEFAULT '#115948', -- Hex color
    sort_order INT DEFAULT 1,                       -- Display order
    replaces_link VARCHAR(255) NULL,                -- Which default link to replace
    is_active BOOLEAN DEFAULT TRUE,                 -- Enable/disable rule
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_group_replaces (microsoft_group_name, replaces_link)
);
```

## Response Format

### Personalized Links Response
```json
[
  {
    "id": 123,
    "name": "Asset Management",
    "url": "https://asset-management.company.com",
    "logo": "/assets/asset-management.png",
    "background_color": "#2563eb",
    "sort_order": 1,
    "is_personalized": true,
    "group_access": "IT Admin"
  },
  {
    "id": 456,
    "name": "Keka",
    "url": "https://keka.com", 
    "logo": "/assets/keka.png",
    "background_color": "#115948",
    "sort_order": 2,
    "is_personalized": false
  }
]
```

### Access Info Response
```json
{
  "user_groups": ["IT Admin", "All Employees"],
  "available_personalized_links": [
    {
      "group": "IT Admin",
      "link_name": "Asset Management",
      "replaces": "outlook",
      "type": "replacement"
    },
    {
      "group": "IT Admin", 
      "link_name": "Server Monitoring",
      "replaces": null,
      "type": "additional"
    }
  ],
  "total_groups": 2,
  "total_personalized_links": 2
}
```

## Advanced Use Cases

### 1. Hierarchical Access
```sql
-- Senior IT gets everything IT gets, plus more
INSERT INTO group_personalized_links (microsoft_group_name, link_name, link_url, replaces_link) VALUES
('IT Admin', 'Asset Management', 'https://asset-management.com', 'outlook'),
('Senior IT Admin', 'Asset Management', 'https://asset-management.com', 'outlook'),
('Senior IT Admin', 'Admin Panel', 'https://admin.company.com', NULL);
```

### 2. Department-Specific Replacements
```sql
-- Different departments get different systems
INSERT INTO group_personalized_links (microsoft_group_name, link_name, link_url, replaces_link) VALUES
('Sales Team', 'CRM System', 'https://crm.company.com', 'zoho'),
('Support Team', 'Ticket System', 'https://tickets.company.com', 'zoho'),
('Development Team', 'Project Manager', 'https://projects.company.com', 'zoho');
```

### 3. Time-Based Access (via is_active)
```sql
-- Temporarily disable access rules
UPDATE group_personalized_links 
SET is_active = FALSE 
WHERE microsoft_group_name = 'Interns' 
  AND link_name = 'Admin Tools';
```

## Troubleshooting

### Links Not Changing
1. **Check User Groups**: Use `/api/links/access-info` to see user's groups
2. **Verify Group Names**: Ensure group names in database match Azure AD exactly
3. **Check is_active**: Ensure access rules are active
4. **Clear Cache**: Restart application if needed

### Multiple Replacements
- Only one replacement per group per target link is allowed
- Database constraint prevents duplicates: `unique_group_replaces`

### Case Sensitivity
- Group names are case-sensitive
- Link names for replacement are case-insensitive (converted to lowercase)

### Debugging
```sql
-- Check what rules exist for your group
SELECT * FROM group_personalized_links 
WHERE microsoft_group_name = 'Your Group Name'
ORDER BY sort_order;

-- Check all active rules
SELECT * FROM group_personalized_links 
WHERE is_active = TRUE 
ORDER BY microsoft_group_name, sort_order;
```

## Security Considerations

1. **Group Verification**: System validates user groups against Microsoft Graph
2. **SQL Injection**: All queries use Eloquent ORM with parameter binding
3. **Authorization**: Only authenticated users can access personalized links
4. **Admin Access**: Consider restricting admin endpoints to specific groups

## Performance Notes

- **Real-time Groups**: User groups fetched from Microsoft Graph session
- **Database Queries**: Efficient queries with proper indexing
- **Caching**: Consider caching personalized links for frequent users
- **Fallback**: System falls back to default links on errors

## Migration from Default System

1. **Backup**: Export current links table before changes
2. **Test**: Create test access rules for your groups first
3. **Gradual**: Start with additional links, then add replacements
4. **Monitor**: Check logs for any errors during transition 