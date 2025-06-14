# Test Access Control System

## Setup Steps

### 1. Run the Database Setup
```bash
# Execute the SQL file to populate groups and access control
mysql -u your_user -p your_database < populate_groups.sql
```

### 2. Verify Database Data
```sql
-- Check groups table
SELECT * FROM groups;

-- Check access control table  
SELECT * FROM group_personalized_links;
```

## Expected Results

### For IT Admin Users:
- **RightPanel** should show "IT Tools" instead of "Outlook"
- **Clicking "IT Tools"** should open `/it-admin-tools` in a new tab
- **New tab** should show the IT Admin Tools page with placeholder content

### For HR Admin Users:
- **RightPanel** should show "HR Tools" instead of "Outlook" 
- **Clicking "HR Tools"** should open `/hr-admin-tools` in a new tab
- **New tab** should show the HR Admin Tools page with placeholder content

### For Other Users:
- **RightPanel** should show "Outlook" 
- **Clicking "Outlook"** should open `https://outlook.office.com` in a new tab

## API Testing

### Check Your Groups
```bash
curl "http://localhost/api/links/access-info"
```

### Check Your Personalized Links
```bash
curl "http://localhost/api/links"
```

### Expected Response for IT Admin
```json
[
  {
    "id": 1,
    "name": "IT Tools",
    "url": "/it-admin-tools",
    "logo": "/assets/it-tools.png", 
    "background_color": "#0066cc",
    "sort_order": 1,
    "is_personalized": true,
    "group_access": "IT Admin"
  }
]
```

### Expected Response for HR Admin
```json
[
  {
    "id": 2, 
    "name": "HR Tools",
    "url": "/hr-admin-tools",
    "logo": "/assets/hr-tools.png",
    "background_color": "#059669", 
    "sort_order": 1,
    "is_personalized": true,
    "group_access": "HR Admin"
  }
]
```

## Files Created

### SQL Files:
- `populate_groups.sql` - Sets up groups and access control data

### React Components:
- `ITAdminTools.jsx` - Blank IT admin tools page
- `HRAdminTools.jsx` - Blank HR admin tools page  

### Updated Files:
- `RightPanel.jsx` - Now shows personalized links based on user groups
- `AppRoutes.jsx` - Added routes for admin tools pages

## Notes

- Links open in **new tabs** to keep the main portal session active
- **No access denied pages** - users only see what they have access to
- **Internal routes** start with `/` (e.g., `/it-admin-tools`)
- **External links** are full URLs (e.g., `https://outlook.office.com`)
- Admin tools pages are **full-screen** without sidebar/right panel 