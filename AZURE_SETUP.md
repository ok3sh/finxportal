# Azure AD Setup for Direct API Memo Approval System

## Overview
This system uses a **direct Microsoft Graph API approach** with **preserved hierarchy logic**, **shared calendar integration**, and **employee directory**:
- **Microsoft Graph API**: Direct source for groups, members, and employees (no caching)
- **Real-time Data**: Fresh data on every request
- **Group Hierarchy**: Local groups table controls approval priorities (1, 2, 3, 4, 5...)
- **Simple Architecture**: No caching complexity, direct API calls
- **Email Notifications**: Automatic email notifications with templates and attachments
- **Shared Calendar**: Display shared Outlook calendar events in dashboard
- **Employee Directory**: Complete company directory with search functionality

## Architecture Benefits
- **Always Fresh Data**: Real-time group membership, user information, and employee data
- **Simple Design**: No cache invalidation or sync complexity
- **Reliable Hierarchy**: Approval workflow enforced through local database
- **Automatic Discovery**: New groups, members, and employees immediately available
- **No Maintenance**: No cache cleanup or sync monitoring required
- **Professional Notifications**: Templated emails with original document attachments
- **Shared Calendar Access**: Access to additional calendars and shared events
- **Comprehensive Directory**: Full employee directory with real-time search

## Required Azure AD Configuration

### 1. App Registration Permissions
Your Azure App Registration needs these **Microsoft Graph permissions**:

```
Application Permissions (for backend services):
- Group.Read.All
- GroupMember.Read.All
- User.Read.All (NEW - for employee directory)

Delegated Permissions (for user authentication):
- Group.Read.All
- GroupMember.Read.All  
- User.Read
- User.Read.All (NEW - for employee directory)
- openid
- profile  
- email
- Calendars.Read (for dashboard features)
- Calendars.Read.Shared (for shared calendar access)
- Mail.Send (for notifications)
```

**⚠️ Important for Direct API System:**
The `GroupMember.Read.All` permission is **required** as the system fetches all group members from Microsoft Graph on every request.

**⚠️ Important for Shared Calendar System:**
The `Calendars.Read.Shared` permission is **required** to access calendars that are shared with the user or calendars where the user is an attendee.

**⚠️ Important for Employee Directory:**
The `User.Read.All` permission is **required** to access all employees in the organization for the directory feature.

### 2. How the Direct API System Works

#### Step 1: Real-time Data Fetching
```javascript
// When /api/group-members is called:
1. Authenticate with stored Microsoft Graph token
2. Fetch all groups from Graph API: GET /v1.0/groups
3. For each group, fetch members: GET /v1.0/groups/{id}/members
4. Map group names to local priorities from groups table
5. Return real-time data with hierarchy information
```

#### Step 2: Hierarchy Integration
```sql
-- groups table (hierarchy control):
{
  "name": "Executives",
  "priority": 1  // Controls approval order
}
```

#### Step 3: Real-time Member Data with Priorities
```json
// API response format (direct + hierarchy):
[
  {
    "id": "azure-uuid-123",
    "name": "John Smith",
    "email": "john@company.com", 
    "group_name": "Executives",
    "group_id": "azure-group-uuid",
    "group_priority": 1  // From local groups table
  }
]
```

## Configuration

### API Timeouts
Optimized for direct API calls:
```php
// Increased timeouts for multiple Graph API calls
'timeout' => 30.0,        // 30 seconds total
'connect_timeout' => 10.0  // 10 seconds connection
```

### Admin Endpoints
```javascript
// Get current statistics  
GET /api/admin/groups/stats
```

## Group Hierarchy Setup (Required)
This system maintains business rules through hierarchy:

### Step 1: Create Azure AD Groups
1. **Go to Azure Portal** > Azure Active Directory > Groups
2. **Create groups** with any names you prefer:
   - "Executives"
   - "Management" 
   - "Senior Staff"
   - "Staff"
   - "Interns"
3. **Add members** to each group

### Step 2: Configure Local Hierarchy
```sql
-- Insert/update priorities in your groups table:
INSERT INTO groups (name, priority) VALUES 
('Executives', 1),      -- Approve first (highest priority)
('Management', 2),      -- Approve after executives
('Senior Staff', 3),    -- Approve after management
('Staff', 4),           -- Approve after senior staff
('Interns', 5);         -- Approve last (lowest priority)
```

### Step 3: Workflow Example
```
Memo raised → Requires: Executives + Management + Staff approval

Approval Order:
1. Executives (priority 1) must approve first
2. Management (priority 2) can approve after executives
3. Staff (priority 4) can approve after management
4. Memo fully approved when all required groups approve
```

## Environment Variables
Add to your `.env`:
```
MICROSOFT_CLIENT_ID=your_client_id
MICROSOFT_CLIENT_SECRET=your_client_secret
MICROSOFT_TENANT_ID=your_tenant_id
```

## Performance Considerations
- **Response Time**: 1-5 seconds depending on group count and Microsoft Graph API performance
- **API Limits**: Subject to Microsoft Graph throttling limits
- **Network Dependency**: Requires stable internet connection to Microsoft Graph
- **Fallback**: Sample data provided when Graph API is unavailable

## Statistics
Monitor your system with:
```bash
curl https://your-domain/api/admin/groups/stats
```

Response:
```json
{
  "total_groups": 8,
  "total_members": 45,
  "data_source": "Microsoft Graph API (Direct)",
  "last_fetch": "2025-01-16T10:30:00Z"
}
```

## What Changed from Cached System

### ✅ Simplified (Architecture)
- **No caching layer**: Direct API calls every time
- **No sync logic**: No staleness detection or cache invalidation
- **No database cache**: Removed microsoft_groups table dependency
- **No sync intervals**: Real-time data on every request

### ✅ Preserved (Business Logic)
- **Approval hierarchy**: Priority-based workflow
- **Group priorities**: 1, 2, 3, 4, 5 order enforcement
- **Authorization**: Only authorized groups can approve
- **Workflow validation**: Higher priority groups must approve first
- **Response format**: Same API response structure

## Benefits of Direct API Approach
1. **Always Current**: No stale data issues
2. **Simple Maintenance**: No cache to manage
3. **Automatic Updates**: New groups/members immediately available
4. **Less Storage**: No local group member storage
5. **Fewer Dependencies**: No Redis or cache management

## Trade-offs
- **Slower Response**: 1-5s vs <10ms with caching
- **API Dependency**: Requires Graph API availability
- **Network Usage**: More API calls to Microsoft
- **Rate Limits**: Subject to Graph API throttling

## Testing
1. **Initial Test**: Make API call to `/api/group-members` - should fetch fresh data
2. **Verify Groups**: Check that all Azure AD groups are returned
3. **Test Hierarchy**: Raise memo and verify approval order enforcement
4. **Test Real-time**: Add new member to Azure AD group, should appear immediately
5. **Test Fallback**: Disconnect internet, should return sample data
6. **Test Email**: Use `/api/debug/test-email` to verify email notifications work
7. **Test Employee Directory**: Click people icon in sidebar to open employee directory

## Employee Directory Feature

### Overview
The employee directory provides a comprehensive, searchable list of all employees in the organization, fetched directly from Microsoft Graph.

### Features
- **Real-time Data**: Always up-to-date employee information from Azure AD
- **Advanced Search**: Search by name, email, job title, department, or group
- **Rich Information**: Shows name, job title, department, office location, and group membership
- **Contact Actions**: Direct email links and copy-to-clipboard functionality
- **Responsive Design**: Card-based layout that works on all screen sizes
- **Modal Interface**: Clean modal overlay that doesn't disrupt workflow

### How It Works
```javascript
// When /api/employees is called:
1. Authenticate with stored Microsoft Graph token
2. Fetch all users from Graph API: GET /v1.0/users
3. Filter for active member accounts only
4. For each user, fetch group membership information
5. Return comprehensive employee data with contact information
```

### Employee Data Structure
```json
// API response format:
[
  {
    "id": "azure-user-uuid",
    "name": "John Smith",
    "email": "john.smith@company.com",
    "job_title": "Senior Developer",
    "department": "Information Technology",
    "office_location": "Tech Hub - Floor 3",
    "group_name": "Senior Staff"
  }
]
```

### User Interface
- **Search Box**: Real-time filtering as you type
- **Employee Cards**: Visual cards showing avatar, name, title, and contact info
- **Grid Layout**: Responsive 3-column grid on desktop, 2-column on tablet, 1-column on mobile
- **Action Buttons**: Direct email and copy email address functionality
- **Refresh Button**: Manual refresh to get latest data from Microsoft Graph

### Performance
- **Load Time**: 2-10 seconds depending on organization size
- **Search**: Instant client-side filtering
- **Fallback**: Sample employee data when Graph API is unavailable
- **Error Handling**: Graceful degradation with helpful error messages

### Testing Employee Directory
1. **Access**: Click the people icon in the sidebar
2. **Search**: Try searching for different terms (name, email, department)
3. **Contact**: Test email links and copy functionality
4. **Refresh**: Use refresh button to get latest data
5. **Fallback**: Disconnect internet and verify fallback data appears

## Email Notifications Feature

### Email Template
The system includes a professional HTML email template with:
- **Header**: Clear decline notification with company branding
- **Content**: Memo details, decline reason, and decliner information
- **Attachment**: Original submitted document automatically attached
- **Footer**: Professional disclaimer and contact information

### Email Template Preview
```html
Subject: Memo Declined: [Memo Description]

❌ Memo Declined

Dear [User Name],

Your memo submission has been declined by the [Group Name] team.

Memo Description: [Description]
Submitted Date: [Date]
Declined by: [Decliner Name] ([Group])

Decline Reason:
[Detailed reason provided by decliner]

📎 Your original document is attached to this email.

Please review the feedback and feel free to resubmit...
```

### Testing Email Functionality
1. **Login with Mail.Send scope**: Ensure you re-authenticate after adding Mail.Send
2. **Test email endpoint**: `GET /api/debug/test-email`
3. **Check your inbox**: Should receive test email
4. **Verify attachment**: Test with actual memo that has document

## Troubleshooting
- **Slow performance**: Check Microsoft Graph API status and network connection
- **Empty groups**: Check Azure AD group membership and API permissions
- **API errors**: Verify access token and Graph API permissions
- **Hierarchy issues**: Verify groups table has correct priorities
- **Approval blocked**: Check that higher priority groups have approved first
- **Timeout errors**: Increase timeout values in MicrosoftGroupSyncService
- **Email not sending**: 
  - Verify `Mail.Send` permission is granted in Azure AD
  - Check that user re-authenticated after adding Mail.Send scope
  - Use `/api/debug/test-email` to test email functionality
  - Check Laravel logs for detailed error messages
  - Ensure access token has mail permissions 

## How the Shared Calendar System Works

### Two-Column Dashboard Layout
```javascript
// Dashboard now has two sections:
1. Main Calendar (60% width) - User's primary calendar/agenda
2. Shared Calendar (40% width) - Secondary calendars and shared events
```

### Shared Calendar Data Sources
```javascript
// The system tries multiple approaches to get shared calendar data:
1. Secondary calendars: GET /v1.0/me/calendars (non-default calendars)
2. Invited events: GET /v1.0/me/events (where user is attendee, not organizer)
3. Fallback data: Sample events when API is unavailable
```

### API Endpoint Structure
```javascript
// New endpoint: /api/shared-calendar
Response format:
{
  "events": [
    {
      "id": "event-id",
      "subject": "Meeting Title",
      "start": { "dateTime": "2025-01-16T10:00:00Z" },
      "end": { "dateTime": "2025-01-16T11:00:00Z" },
      "location": { "displayName": "Conference Room" },
      "organizer": { "emailAddress": { "name": "Organizer Name" } }
    }
  ],
  "count": 5,
  "message": "Shared calendar events retrieved successfully"
}
```

## Dashboard Layout Configuration

### Two-Column Layout
The new dashboard layout uses CSS Flexbox:
```css
/* Main container */
.calendar-container {
  display: flex;
  gap: 1.5rem;
  max-height: 444px;
}

/* Main calendar (60% width) */
.main-calendar {
  flex: 0 0 60%;
  background: #115948;
  border-radius: 1.5rem;
  padding: 1rem;
}

/* Shared calendar (40% width) */
.shared-calendar {
  flex: 0 0 35%;
  background: #115948;
  border-radius: 1.5rem;
  padding: 1rem;
}
```

## Testing
1. **Initial Test**: Make API call to `/api/shared-calendar` - should fetch fresh data
2. **Verify Layout**: Check that dashboard shows two columns
3. **Test Shared Events**: Invite user to events from another account
4. **Test Secondary Calendars**: Create additional calendars in Outlook
5. **Test Fallback**: Disconnect internet, should return sample data
6. **Test Permissions**: Verify `Calendars.Read.Shared` is granted in Azure AD
7. **Test Responsiveness**: Check layout on different screen sizes

## Troubleshooting
- **No shared events**: Check if user has secondary calendars or is invited to events
- **Permission errors**: Verify `Calendars.Read.Shared` is granted and user re-authenticated
- **Layout issues**: Check CSS flexbox support and container dimensions
- **API timeouts**: Increase timeout values in SharedCalendarController
- **Empty shared calendar**: Normal if user has no secondary calendars or invitations
- **Slow loading**: Shared calendar loads independently, won't block main calendar

## Re-Authentication Required
After adding `Calendars.Read.Shared` permission:
1. **Update Azure AD**: Add the new permission to your app registration
2. **Re-authenticate**: Users must log out and log back in
3. **Verify Token**: New access token should include shared calendar scopes
4. **Test Access**: Try `/api/shared-calendar` endpoint 