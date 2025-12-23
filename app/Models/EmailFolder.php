<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailFolder extends Model
{
    protected $fillable = [
        'name',
        'color',
        'icon',
        'sort_order',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the emails in this folder
     */
    public function emails(): HasMany
    {
        return $this->hasMany(AdminEmail::class, 'folder_id');
    }

    /**
     * Get the unread count for this folder
     */
    public function getUnreadCountAttribute(): int
    {
        return $this->emails()->where('is_read', false)->count();
    }

    /**
     * Get the total count for this folder
     */
    public function getTotalCountAttribute(): int
    {
        return $this->emails()->count();
    }

    /**
     * Scope for system folders
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope for user folders
     */
    public function scopeUser($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope for ordering folders
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('is_system', 'desc')->orderBy('sort_order')->orderBy('name');
    }
}
