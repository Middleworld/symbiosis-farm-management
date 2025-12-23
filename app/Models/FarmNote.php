<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FarmNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'title',
        'content',
        'category',
        'tags',
        'is_public',
        'is_pinned',
        'task_id',
        'farmos_log_id',
        'farmos_synced_at',
        'created_by',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_public' => 'boolean',
        'is_pinned' => 'boolean',
        'farmos_synced_at' => 'datetime',
    ];

    /**
     * Note belongs to creator
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Note optionally belongs to a task
     */
    public function task()
    {
        return $this->belongsTo(FarmTask::class, 'task_id');
    }

    /**
     * Scope for public notes
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for pinned notes
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope for specific type
     */
    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }
}
