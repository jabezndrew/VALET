<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedbacks';

    protected $fillable = [
        'user_id',
        'type',
        'message',
        'rating',
        'email',
        'issues',
        'device_info',
        'status',
        'admin_response',
        'admin_id',
        'responded_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'admin_id' => 'integer',
        'rating' => 'integer',
        'issues' => 'array',
        'device_info' => 'array',
        'responded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [];

    // Type constants
    const TYPE_GENERAL = 'general';
    const TYPE_BUG = 'bug';
    const TYPE_FEATURE = 'feature';
    const TYPE_PARKING = 'parking';
    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_REVIEWED = 'reviewed';
    const STATUS_RESOLVED = 'resolved';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(SysUser::class, 'user_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(SysUser::class, 'admin_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeReviewed($query)
    {
        return $query->where('status', self::STATUS_REVIEWED);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Status checks
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function hasResponse(): bool
    {
        return !empty($this->admin_response);
    }

    public function markAsReviewed(): bool
    {
        return $this->update(['status' => self::STATUS_REVIEWED]);
    }

    public function markAsResolved(): bool
    {
        return $this->update(['status' => self::STATUS_RESOLVED]);
    }

    public function respond(string $response, int $adminId): bool
    {
        return $this->update([
            'admin_response' => $response,
            'admin_id' => $adminId,
            'responded_at' => now(),
            'status' => self::STATUS_REVIEWED,
        ]);
    }

    // Display helpers
    public function getTypeDisplayName(): string
    {
        return match($this->type) {
            self::TYPE_GENERAL  => 'General Feedback',
            self::TYPE_BUG      => 'Bug Report',
            self::TYPE_FEATURE  => 'Feature Request',
            self::TYPE_PARKING  => 'Parking Issue',
            default             => ucfirst($this->type),
        };
    }

    public function getStatusDisplayName(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_REVIEWED => 'Reviewed',
            self::STATUS_RESOLVED => 'Resolved',
            default => ucfirst($this->status),
        };
    }

    public function getStatusBadgeColor(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_REVIEWED => 'info',
            self::STATUS_RESOLVED => 'success',
            default => 'secondary',
        };
    }
}
