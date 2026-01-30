<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Feedback Model
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $message
 * @property int|null $rating
 * @property string|null $email
 * @property array $issues
 * @property array $device_info
 * @property string $status
 * @property string|null $admin_response
 * @property int|null $admin_id
 * @property \Illuminate\Support\Carbon|null $responded_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read SysUser $user
 * @property-read SysUser|null $admin
 */
class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedbacks';

    /**
     * The attributes that are mass assignable.
     */
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

    /**
     * The attributes that should be cast.
     */
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

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [];

    /**
     * Valid feedback types
     */
    const TYPE_GENERAL = 'general';
    const TYPE_BUG = 'bug';
    const TYPE_FEATURE = 'feature';
    const TYPE_PARKING = 'parking';
    const TYPE_GUARD_REPORT = 'guard_report';

    /**
     * Valid feedback statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_REVIEWED = 'reviewed';
    const STATUS_RESOLVED = 'resolved';

    /**
     * Get the user who created the feedback
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(SysUser::class, 'user_id');
    }

    /**
     * Get the admin who responded to the feedback
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(SysUser::class, 'admin_id');
    }

    /**
     * Scope for pending feedbacks
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for reviewed feedbacks
     */
    public function scopeReviewed($query)
    {
        return $query->where('status', self::STATUS_REVIEWED);
    }

    /**
     * Scope for resolved feedbacks
     */
    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    /**
     * Scope for specific feedback type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for feedbacks created in the last N days
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Check if feedback is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if feedback has been responded to
     */
    public function hasResponse(): bool
    {
        return !empty($this->admin_response);
    }

    /**
     * Mark as reviewed
     */
    public function markAsReviewed(): bool
    {
        return $this->update(['status' => self::STATUS_REVIEWED]);
    }

    /**
     * Mark as resolved
     */
    public function markAsResolved(): bool
    {
        return $this->update(['status' => self::STATUS_RESOLVED]);
    }

    /**
     * Respond to the feedback
     */
    public function respond(string $response, int $adminId): bool
    {
        return $this->update([
            'admin_response' => $response,
            'admin_id' => $adminId,
            'responded_at' => now(),
            'status' => self::STATUS_REVIEWED,
        ]);
    }

    /**
     * Get the type display name
     */
    public function getTypeDisplayName(): string
    {
        return match($this->type) {
            self::TYPE_GENERAL => 'General Feedback',
            self::TYPE_BUG => 'Bug Report',
            self::TYPE_FEATURE => 'Feature Request',
            self::TYPE_PARKING => 'Parking Issue',
            self::TYPE_GUARD_REPORT => 'Guard Report',
            default => ucfirst($this->type),
        };
    }

    /**
     * Check if this is a guard report
     */
    public function isGuardReport(): bool
    {
        return $this->type === self::TYPE_GUARD_REPORT;
    }

    /**
     * Get the status display name
     */
    public function getStatusDisplayName(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_REVIEWED => 'Reviewed',
            self::STATUS_RESOLVED => 'Resolved',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status badge color
     */
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
