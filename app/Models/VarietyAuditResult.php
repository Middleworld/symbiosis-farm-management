<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VarietyAuditResult extends Model
{
    protected $fillable = [
        'variety_id',
        'audit_run_id',
        'issue_description',
        'severity',
        'confidence',
        'suggested_field',
        'current_value',
        'suggested_value',
        'status',
        'reviewed_by',
        'reviewed_at',
        'applied_at',
        'review_notes',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    /**
     * Relationship to plant variety
     */
    public function variety(): BelongsTo
    {
        return $this->belongsTo(PlantVariety::class, 'variety_id');
    }

    /**
     * Scope: Only pending suggestions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Only approved suggestions
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope: Critical severity
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Scope: High confidence
     */
    public function scopeHighConfidence($query)
    {
        return $query->where('confidence', 'high');
    }

    /**
     * Scope: Group by audit run
     */
    public function scopeFromRun($query, string $runId)
    {
        return $query->where('audit_run_id', $runId);
    }

    /**
     * Approve this suggestion
     */
    public function approve(?int $userId = null, ?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    /**
     * Reject this suggestion
     */
    public function reject(?int $userId = null, ?string $notes = null): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    /**
     * Mark as applied
     */
    public function markApplied(): void
    {
        $this->update([
            'status' => 'applied',
            'applied_at' => now(),
        ]);
    }

    /**
     * Get severity badge color
     */
    public function getSeverityBadgeAttribute(): string
    {
        return match($this->severity) {
            'critical' => 'danger',
            'warning' => 'warning',
            'info' => 'info',
            default => 'secondary',
        };
    }

    /**
     * Get confidence badge color
     */
    public function getConfidenceBadgeAttribute(): string
    {
        return match($this->confidence) {
            'high' => 'success',
            'medium' => 'warning',
            'low' => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'applied' => 'primary',
            default => 'secondary',
        };
    }
}
