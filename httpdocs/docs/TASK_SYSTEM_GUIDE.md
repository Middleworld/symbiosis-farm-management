# Task Management System Guide

## Overview
A comprehensive task management system for both web development and administrative staff tasks, built into the Laravel admin panel.

## Features
- **Two Task Types**: Dev (development tasks) and Farm (admin staff tasks)
- **Kanban Board**: Drag-and-drop interface for visual task management
- **List View**: Filterable and sortable task list with statistics
- **Task Details**: Full task view with comments and file attachments
- **Priority & Status**: Track task urgency and progress
- **Time Tracking**: Estimated and actual hours
- **Comments**: Team collaboration on tasks
- **File Attachments**: Upload and manage task-related files

## Getting Started

### Access
Navigate to **Tasks** in the left sidebar under **Operations**

### Quick Actions
1. **Create a Task**: Click "New Task" from list or kanban view
2. **View Tasks**: Choose List or Kanban view (tabs at top)
3. **Filter Tasks**: Use filter buttons (My Tasks, Overdue, High Priority, etc.)
4. **Update Status**: Drag tasks between kanban columns or edit task
5. **Add Comments**: Open task detail view and scroll to comments section
6. **Upload Files**: Open task detail view and use attachment uploader

## Task Types

### Dev Tasks (Development)
- Categories: Bug Fix, Feature, Enhancement, Maintenance, Documentation
- Used by development team for software projects
- Includes dev_category field

### Farm Tasks (Admin Staff)
- General administrative tasks
- Used by office/admin staff
- No category field required

## Task Statuses
1. **To Do**: Not started
2. **In Progress**: Currently being worked on
3. **Review**: Ready for review/testing
4. **Completed**: Finished successfully
5. **Cancelled**: No longer needed

## Priorities
- **Low**: Can wait
- **Medium**: Normal priority (default)
- **High**: Important
- **Urgent**: Needs immediate attention

## Views

### List View (`/admin/tasks`)
- Tabbed by task type (Dev / Admin)
- Statistics cards showing task counts
- Filterable table with all task details
- Quick edit and delete actions

### Kanban Board (`/admin/tasks/kanban`)
- Visual drag-and-drop interface
- Four columns (To Do, In Progress, Review, Completed)
- Real-time status updates
- Column counts

### Task Detail (`/admin/tasks/{id}`)
- Full task information
- Comments section
- File attachments
- Activity timeline
- Quick status change buttons

### Create/Edit Forms
- All task fields
- Validation and error handling
- User assignment dropdown
- Date picker for due dates

## Database Tables

### farm_tasks
- Main task table with all task fields
- Supports both dev and farm types
- Tracks creator, assignee, dates, hours

### farm_notes
- Standalone notes (future feature)
- Can be linked to tasks
- Support for categories and tags

### task_comments
- Comments on tasks
- Tracks user and timestamp
- Displayed in task detail view

### task_attachments
- File uploads for tasks
- Auto-deletion when removed
- Displays file info (name, size, uploader)

## API Endpoints

### Task Routes
- `GET /admin/tasks` - List tasks (with filters)
- `GET /admin/tasks/kanban` - Kanban board view
- `GET /admin/tasks/create` - Create task form
- `POST /admin/tasks` - Store new task
- `GET /admin/tasks/{id}` - Show task detail
- `GET /admin/tasks/{id}/edit` - Edit task form
- `PUT /admin/tasks/{id}` - Update task
- `DELETE /admin/tasks/{id}` - Delete task
- `PATCH /admin/tasks/{id}/status` - Update status (AJAX)
- `POST /admin/tasks/{id}/comments` - Add comment
- `POST /admin/tasks/{id}/attachments` - Upload file
- `DELETE /admin/tasks/{id}/attachments/{attachmentId}` - Delete file

### Note Routes (Backend ready, views pending)
- `GET /admin/notes` - List notes
- `POST /admin/notes` - Create note
- `GET /admin/notes/{id}` - Show note
- `PUT /admin/notes/{id}` - Update note
- `DELETE /admin/notes/{id}` - Delete note

## Usage Examples

### Creating a Development Task
1. Click "New Task" button
2. Select task type: Dev
3. Fill in:
   - Title: "Fix login bug on mobile"
   - Description: Detailed explanation
   - Category: Bug Fix
   - Priority: High
   - Assign to: Developer
   - Due date: Next week
   - Estimated hours: 4
4. Click "Create Task"

### Creating an Admin Task
1. Click "New Task" button
2. Select task type: Farm (Admin)
3. Fill in:
   - Title: "Review customer invoices"
   - Description: Check Q4 invoices
   - Priority: Medium
   - Assign to: Admin staff
   - Due date: End of month
4. Click "Create Task"

### Using Kanban Board
1. Navigate to Tasks > Kanban
2. See tasks organized by status
3. Drag task card to new column to update status
4. Status updates automatically via AJAX
5. See column counts update in real-time

### Adding Comments
1. Open task detail view
2. Scroll to Comments section
3. Type comment in textarea
4. Click "Post Comment"
5. Comment appears with your name and timestamp

### Uploading Files
1. Open task detail view
2. Find Attachments section in right sidebar
3. Click "Choose File"
4. Select file (max 10MB)
5. Click "Upload"
6. File appears in attachments list

## Tips
- Use filters to focus on relevant tasks (My Tasks, Overdue, etc.)
- Set due dates to avoid tasks getting lost
- Add comments for team communication
- Use priority levels to highlight urgent work
- Track actual hours for better future estimates
- Attach relevant files directly to tasks
- Use kanban board for quick status management

## Future Enhancements
- Notes management views
- FarmOS task viewer (read-only farm field tasks)
- Email notifications for task assignments
- Task templates for recurring work
- Advanced filtering and search
- Time tracking with start/stop timer
- Task dependencies
- Recurring tasks

## Technical Details
- Built with Laravel 12.0
- Bootstrap 5 UI
- AJAX for kanban drag-drop
- File storage in `storage/app/task-attachments/`
- Eloquent models with relationships
- Form validation and error handling
- Responsive design for mobile use

## Support
For issues or feature requests, create a dev task with category "Bug Fix" or "Enhancement"!
