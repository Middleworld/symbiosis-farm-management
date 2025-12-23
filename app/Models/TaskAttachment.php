<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TaskAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'uploaded_by',
        'filename',
        'filepath',
        'mime_type',
        'filesize',
    ];

    /**
     * Attachment belongs to a task
     */
    public function task()
    {
        return $this->belongsTo(FarmTask::class, 'task_id');
    }

    /**
     * Get uploader from config
     */
    public function uploader()
    {
        $users = config('admin_users.users', []);
        $user = collect($users)->firstWhere('email', $this->uploaded_by);
        
        if (!$user) {
            return (object)[
                'id' => $this->uploaded_by,
                'name' => 'Unknown User',
                'email' => $this->uploaded_by,
            ];
        }
        
        return (object)[
            'id' => $user['email'],
            'name' => $user['name'],
            'email' => $user['email'],
        ];
    }

    /**
     * Get file URL
     */
    public function getUrlAttribute()
    {
        return Storage::url($this->filepath);
    }

    /**
     * Get human-readable file size
     */
    public function getFilesizeHumanAttribute()
    {
        $bytes = $this->filesize;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    /**
     * Delete file when model is deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($attachment) {
            if (Storage::exists($attachment->filepath)) {
                Storage::delete($attachment->filepath);
            }
        });
    }
}
