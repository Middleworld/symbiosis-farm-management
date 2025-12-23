<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FarmTask;
use App\Models\TaskComment;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    /**
     * Get current admin user email from session
     */
    private function getCurrentUserEmail()
    {
        $adminUser = Session::get('admin_user', []);
        return $adminUser['email'] ?? null;
    }
    
    /**
     * Get admin users from config
     * Returns a collection formatted to match User model structure
     */
    private function getAdminUsers($type = null)
    {
        $users = config('admin_users.users', []);
        
        // Filter by active users only
        $users = array_filter($users, fn($user) => $user['active'] ?? true);
        
        // Filter by task type if specified
        if ($type === 'dev') {
            $users = array_filter($users, fn($user) => $user['is_webdev'] ?? false);
        } elseif ($type === 'farm') {
            $users = array_filter($users, fn($user) => $user['is_admin'] ?? false);
        }
        
        // Convert to objects with id (email-based) for compatibility
        return collect($users)->map(function($user, $index) {
            return (object)[
                'id' => $user['email'], // Use email as ID
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'] ?? 'admin',
                'is_admin' => $user['is_admin'] ?? false,
                'is_webdev' => $user['is_webdev'] ?? false,
            ];
        })->sortBy('name')->values();
    }
    
    /**
     * Display task dashboard
     */
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $type = $request->get('type', 'dev');
        
        $query = FarmTask::where('type', $type);
        
        // Apply filters
        switch ($filter) {
            case 'my-tasks':
                $query->where('assigned_to', $this->getCurrentUserEmail());
                break;
            case 'created-by-me':
                $query->where('created_by', $this->getCurrentUserEmail());
                break;
            case 'unassigned':
                $query->whereNull('assigned_to');
                break;
            case 'overdue':
                $query->overdue();
                break;
            case 'high-priority':
                $query->highPriority();
                break;
        }
        
        $tasks = $query->orderBy('priority', 'desc')
            ->orderBy('due_date', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);
        
        // Get stats
        $stats = [
            'total' => FarmTask::where('type', $type)->count(),
            'todo' => FarmTask::where('type', $type)->status('todo')->count(),
            'in_progress' => FarmTask::where('type', $type)->status('in_progress')->count(),
            'review' => FarmTask::where('type', $type)->status('review')->count(),
            'completed' => FarmTask::where('type', $type)->status('completed')->count(),
            'my_tasks' => FarmTask::where('type', $type)->where('assigned_to', $this->getCurrentUserEmail())->count(),
            'overdue' => FarmTask::where('type', $type)->overdue()->count(),
        ];
        
        $users = $this->getAdminUsers($type);
        
        return view('admin.tasks.index', compact('tasks', 'stats', 'filter', 'type', 'users'));
    }
    
    /**
     * Display Kanban board view
     */
    public function kanban(Request $request)
    {
        $type = $request->get('type', 'dev');
        
        $tasksByStatus = FarmTask::where('type', $type)
            ->orderBy('priority', 'desc')
            ->orderBy('due_date', 'asc')
            ->get()
            ->groupBy('status');
        
        // Ensure all status columns exist even if empty
        $tasks = collect([
            'todo' => $tasksByStatus->get('todo', collect()),
            'in_progress' => $tasksByStatus->get('in_progress', collect()),
            'review' => $tasksByStatus->get('review', collect()),
            'completed' => $tasksByStatus->get('completed', collect()),
        ]);
        
        $users = $this->getAdminUsers($type);
        
        return view('admin.tasks.kanban', compact('tasks', 'type', 'users'));
    }
    
    /**
     * Show create task form
     */
    public function create(Request $request)
    {
        $type = $request->get('type', 'dev');
        $users = $this->getAdminUsers($type);
        
        return view('admin.tasks.create', compact('type', 'users'));
    }
    
    /**
     * Store new task
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:farm,dev',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:todo,in_progress,review,completed,cancelled',
            'priority' => 'required|in:low,medium,high,urgent',
            'dev_category' => 'nullable|in:bug,feature,enhancement,maintenance,documentation',
            'assigned_to' => 'nullable|string|email',
            'due_date' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0',
        ]);
        
        $validated['created_by'] = $this->getCurrentUserEmail();
        
        $task = FarmTask::create($validated);
        
        return redirect()->route('admin.tasks.show', $task)
            ->with('success', 'Task created successfully!');
    }
    
    /**
     * Show task details
     */
    public function show(FarmTask $task)
    {
        $task->load(['comments', 'attachments']);
        $users = $this->getAdminUsers($task->type);
        
        return view('admin.tasks.show', compact('task', 'users'));
    }
    
    /**
     * Show edit task form
     */
    public function edit(FarmTask $task)
    {
        $users = $this->getAdminUsers($task->type);
        
        return view('admin.tasks.edit', compact('task', 'users'));
    }
    
    /**
     * Update task
     */
    public function update(Request $request, FarmTask $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:todo,in_progress,review,completed,cancelled',
            'priority' => 'required|in:low,medium,high,urgent',
            'dev_category' => 'nullable|in:bug,feature,enhancement,maintenance,documentation',
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
        ]);
        
        // Mark completed timestamp if status changed to completed
        if ($validated['status'] === 'completed' && $task->status !== 'completed') {
            $validated['completed_at'] = now();
        } elseif ($validated['status'] !== 'completed') {
            $validated['completed_at'] = null;
        }
        
        $task->update($validated);
        
        return redirect()->route('admin.tasks.show', $task)
            ->with('success', 'Task updated successfully!');
    }
    
    /**
     * Update task status (AJAX for kanban)
     */
    public function updateStatus(Request $request, FarmTask $task)
    {
        $validated = $request->validate([
            'status' => 'required|in:todo,in_progress,review,completed,cancelled',
        ]);
        
        if ($validated['status'] === 'completed' && $task->status !== 'completed') {
            $validated['completed_at'] = now();
        } elseif ($validated['status'] !== 'completed') {
            $validated['completed_at'] = null;
        }
        
        $task->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Task status updated!',
            'task' => $task->fresh(),
        ]);
    }
    
    /**
     * Delete task
     */
    public function destroy(FarmTask $task)
    {
        $task->delete();
        
        return redirect()->route('admin.tasks.index')
            ->with('success', 'Task deleted successfully!');
    }
    
    /**
     * Add comment to task
     */
    public function addComment(Request $request, FarmTask $task)
    {
        $validated = $request->validate([
            'comment' => 'required|string',
        ]);
        
        $comment = $task->comments()->create([
            'user_id' => $this->getCurrentUserEmail(),
            'comment' => $validated['comment'],
        ]);
        
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'comment' => $comment->load('user'),
            ]);
        }
        
        return redirect()->route('admin.tasks.show', $task)
            ->with('success', 'Comment added successfully!');
    }
    
    /**
     * Upload attachment
     */
    public function uploadAttachment(Request $request, FarmTask $task)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,pdf,csv,txt,md,doc,docx,xls,xlsx,zip|max:10240', // 10MB max
        ]);
        
        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        $path = $file->store('task-attachments', 'public');
        
        $attachment = $task->attachments()->create([
            'uploaded_by' => $this->getCurrentUserEmail(),
            'filename' => $filename,
            'filepath' => $path,
            'mime_type' => $file->getMimeType(),
            'filesize' => $file->getSize(),
        ]);
        
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'attachment' => $attachment->load('uploader'),
            ]);
        }
        
        return redirect()->route('admin.tasks.show', $task)
            ->with('success', 'File uploaded!');
    }
    
    /**
     * Delete attachment
     */
    public function deleteAttachment(FarmTask $task, TaskAttachment $attachment)
    {
        $attachment->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Attachment deleted!',
        ]);
    }
}
