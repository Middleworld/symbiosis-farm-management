# Admin User Management System

## Overview
Complete admin user management with automatic WordPress and FarmOS account creation for single sign-on across all three systems.

## Features

### User Management
- Create new admin users
- Edit existing users (name, role, permissions, password)
- Deactivate users without deleting
- Delete users (cannot delete yourself)
- View all admin users with their roles and permissions

### Automatic Account Creation
When you create a new admin user, the system automatically:

1. **Admin Panel Account**: Adds user to `config/admin_users.php`
2. **WordPress Account**: Creates administrator account via WP REST API
3. **FarmOS Account**: Creates farm manager account via FarmOS API

All three accounts use the same username and password for seamless single sign-on.

### Role Types

#### Admin
- Standard administrative access
- Can access admin panel features
- Can be assigned tasks based on permissions

#### Super Admin  
- Full system access
- Can create, edit, and delete admin users
- Can manage all system settings

### Task Permissions

Users can have one or both task permissions:

- **Admin Tasks**: Can be assigned general administrative tasks
- **Dev Tasks**: Can be assigned web development tasks

When creating tasks, only users with the appropriate permission will appear in the assignment dropdown.

## Usage

### Creating a New Admin User

1. Navigate to **System > Admin Users**
2. Click **Add New Admin User**
3. Fill in the form:
   - **Name**: Full name of the user
   - **Email**: Primary email (used for admin panel and FarmOS)
   - **WordPress Email** (optional): Different email for WordPress if needed
   - **Password**: Minimum 8 characters (used across all systems)
   - **Role**: Admin or Super Admin
   - **Task Permissions**: Check Admin Tasks and/or Dev Tasks
4. Click **Create Admin User**

The system will:
- Add the user to the config file
- Create WordPress admin account
- Create FarmOS farm manager account
- Set up email mapping if WordPress email differs
- Clear config cache

### Editing an Admin User

1. Navigate to **System > Admin Users**
2. Click the edit icon for the user
3. Modify:
   - Name
   - Role
   - Task permissions
   - Active status
   - Password (optional - leave blank to keep current)
4. Click **Update User**

**Note**: Email address cannot be changed after creation.

### Deactivating a User

1. Edit the user
2. Uncheck **Active (user can log in)**
3. Save

The user will no longer be able to log in but their account data is preserved.

### Deleting a User

1. Click the delete (trash) icon
2. Confirm deletion

**Note**: You cannot delete your own account while logged in.

## Technical Details

### Storage
Admin users are stored in `/config/admin_users.php` with the following structure:

```php
[
    'name' => 'User Name',
    'email' => 'user@example.com',
    'password' => 'plain_text_password',
    'role' => 'admin', // or 'super_admin'
    'is_admin' => true,
    'is_webdev' => false,
    'created_at' => '2025-10-11',
    'active' => true,
]
```

### WordPress Integration
- Uses WP REST API to create users
- Assigns 'administrator' role
- Username generated from email (replaces @ and . with _)
- Handles email mapping for different WP/admin emails

### FarmOS Integration
- Uses FarmOS API to create users
- Assigns 'farm_manager' role
- Username generated from email
- Provides farm management permissions

### Single Sign-On
When an admin user logs into the admin panel:
1. Credentials validated against config file
2. Automatic WordPress authentication via WpApiService
3. Session stores WP authentication status
4. User can access WordPress admin without re-login
5. FarmOS uses same credentials when accessed

### Security Considerations

⚠️ **Important**:
- Passwords stored in plain text in config file (current system design)
- Config file should have restricted permissions (600 or 640)
- Only use on secure, private servers
- Consider implementing password hashing in future updates
- Super Admin role should be limited to trusted staff only

### API Endpoints

Routes are located at `/admin/admin-users/*`:
- `GET /admin/admin-users` - List all users
- `GET /admin/admin-users/create` - Create form
- `POST /admin/admin-users` - Store new user
- `GET /admin/admin-users/{index}/edit` - Edit form
- `PUT /admin/admin-users/{index}` - Update user
- `DELETE /admin/admin-users/{index}` - Delete user

### Error Handling

If WordPress or FarmOS account creation fails:
- Admin panel account is still created
- Error message displayed with details
- User can manually create WP/FarmOS accounts later
- Logs written for troubleshooting

### Troubleshooting

**Config file not updating**:
- Check file permissions on `config/admin_users.php`
- Ensure web server has write access
- Run `php artisan config:clear` after changes

**WordPress creation fails**:
- Verify WP REST API is accessible
- Check WordPress API credentials in settings
- Ensure WordPress allows admin creation via API

**FarmOS creation fails**:
- Verify FarmOS API connection
- Check FarmOS admin credentials
- Ensure FarmOS user registration is enabled

**User can't log in**:
- Verify user is marked as 'active'
- Check password is correct
- Look for login attempt logs

## Future Enhancements

Potential improvements:
- Password hashing instead of plain text storage
- Database-backed user storage
- Two-factor authentication
- Password reset functionality
- User activity logging
- Bulk user operations
- CSV import/export
- Role-based permission system
- API token generation for users

## Related Documentation

- See `TASK_SYSTEM_GUIDE.md` for task management details
- WordPress API integration in `WpApiService.php`
- FarmOS API integration in `FarmOSApi.php`
- Login system in `LoginController.php`
