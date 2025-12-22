<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'comment',
    ];

    /**
     * Comment belongs to a task
     */
    public function task()
    {
        return $this->belongsTo(FarmTask::class, 'task_id');
    }

    /**
     * Get comment user from config
     */
    public function user()
    {
        $users = config('admin_users.users', []);
        $user = collect($users)->firstWhere('email', $this->user_id);
        
        if (!$user) {
            return (object)[
                'id' => $this->user_id,
                'name' => 'Unknown User',
                'email' => $this->user_id,
            ];
        }
        
        return (object)[
            'id' => $user['email'],
            'name' => $user['name'],
            'email' => $user['email'],
        ];
    }
}
