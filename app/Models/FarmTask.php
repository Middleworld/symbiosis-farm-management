<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FarmTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'title',
        'description',
        'status',
        'priority',
        'dev_category',
        'farmos_log_type',
        'farmos_log_id',
        'farmos_synced_at',
        'assigned_to',
        'created_by',
        'is_farm_wide',
        'due_date',
        'completed_at',
        'location',
        'estimated_hours',
        'actual_hours',
    ];

    protected $casts = [
        'is_farm_wide' => 'boolean',
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'farmos_synced_at' => 'datetime',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
    ];

    /**
     * Task belongs to assigned user
     */
    /**
     * Get assigned user from config
     */
    public function getAssignedTo()
    {
        if (!$this->assigned_to) {
            return null;
        }
        
        $users = config('admin_users.users', []);
        $user = collect($users)->firstWhere('email', $this->assigned_to);
        
        if (!$user) {
            return null;
        }
        
        return (object)[
            'id' => $user['email'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'] ?? 'admin',
        ];
    }

    /**
     * Get creator user from config
     */
    public function getCreatedBy()
    {
        $users = config('admin_users.users', []);
        $user = collect($users)->firstWhere('email', $this->created_by);
        
        if (!$user) {
            // Return a default object if user not found
            return (object)[
                'id' => $this->created_by,
                'name' => 'Unknown User',
                'email' => $this->created_by,
                'role' => 'admin',
            ];
        }
        
        return (object)[
            'id' => $user['email'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'] ?? 'admin',
        ];
    }

    /**
     * Task has many comments
     */
    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'task_id')->orderBy('created_at', 'desc');
    }

    /**
     * Task has many attachments
     */
    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class, 'task_id');
    }

    /**
     * Scope for dev tasks only
     */
    public function scopeDevTasks($query)
    {
        return $query->where('type', 'dev');
    }

    /**
     * Scope for farm tasks only
     */
    public function scopeFarmTasks($query)
    {
        return $query->where('type', 'farm');
    }

    /**
     * Scope for assigned to specific user
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope for created by specific user
     */
    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope for specific status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for overdue tasks
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled');
    }

    /**
     * Scope for high priority
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    /**
     * Check if task is overdue
     */
    public function isOverdue()
    {
        return $this->due_date 
            && $this->due_date->isPast() 
            && !in_array($this->status, ['completed', 'cancelled']);
    }

    /**
     * Mark task as completed
     */
    public function markCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'todo' => 'secondary',
            'in_progress' => 'primary',
            'review' => 'warning',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get priority badge color
     */
    public function getPriorityBadgeAttribute()
    {
        return match($this->priority) {
            'low' => 'info',
            'medium' => 'secondary',
            'high' => 'warning',
            'urgent' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get human-readable category label
     */
    public function getCategoryLabelAttribute()
    {
        if ($this->type === 'dev') {
            return match($this->dev_category) {
                'bug' => '🐛 Bug',
                'feature' => '✨ Feature',
                'enhancement' => '⚡ Enhancement',
                'maintenance' => '🔧 Maintenance',
                'documentation' => '📚 Documentation',
                default => 'General',
            };
        }
        return 'Admin Task';
    }
}
