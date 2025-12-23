<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class AdminEmail extends Model
{
    protected $fillable = [
        'account_id',
        'folder',
        'message_id',
        'from_email',
        'from_name',
        'to_email',
        'cc_email',
        'bcc_email',
        'subject',
        'body',
        'attachments',
        'sent_at',
        'received_at',
        'is_read',
        'is_starred',
        'is_important',
        'is_flagged',
        'thread_id',
    ];

    protected $casts = [
        'attachments' => 'array',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'is_read' => 'boolean',
        'is_starred' => 'boolean',
        'is_important' => 'boolean',
        'is_flagged' => 'boolean',
    ];

    /**
     * Get the sender display name
     */
    public function getSenderAttribute()
    {
        return $this->from_name ?: $this->from_email;
    }

    /**
     * Get formatted received date
     */
    public function getFormattedReceivedAtAttribute()
    {
        if (!$this->received_at) return '';

        $now = now();
        $diff = $this->received_at->diffInDays($now);

        if ($diff === 0) {
            return $this->received_at->format('H:i');
        } elseif ($diff === 1) {
            return 'Yesterday';
        } elseif ($diff < 7) {
            return $this->received_at->format('D');
        } else {
            return $this->received_at->format('M j');
        }
    }

    /**
     * Get preview text (first 100 characters)
     */
    public function getPreviewAttribute()
    {
        return substr(strip_tags($this->body), 0, 100) . '...';
    }

    /**
     * Scope for unread emails
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for starred emails
     */
    public function scopeStarred($query)
    {
        return $query->where('is_starred', true);
    }

    /**
     * Scope for important emails
     */
    public function scopeImportant($query)
    {
        return $query->where('is_important', true);
    }

    /**
     * Get the email account this email belongs to
     */
    public function account()
    {
        return $this->belongsTo(EmailAccount::class, 'account_id');
    }

    /**
     * Get the folder this email belongs to
     */
    public function emailFolder()
    {
        return $this->belongsTo(EmailFolder::class, 'folder_id');
    }
}
