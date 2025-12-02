<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PendingAccount Model
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $role
 * @property string|null $employee_id
 * @property string|null $department
 * @property bool $is_active
 * @property int $created_by
 * @property string $status
 * @property string|null $admin_notes
 * @property int|null $reviewed_by
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read SysUser $creator
 * @property-read SysUser|null $reviewer
 */
class PendingAccount extends Model
{
    use HasFactory;

    protected $table = 'pending_accounts';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'employee_id',
        'department',
        'is_active',
        'created_by',
        'status',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_by' => 'integer',
        'reviewed_by' => 'integer',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Valid account statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    /**
     * Valid roles
     */
    const ROLE_USER = 'user';
    const ROLE_SECURITY = 'security';
    const ROLE_SSD = 'ssd';
    const ROLE_ADMIN = 'admin';

    /**
     * Get the user who created this pending account
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(SysUser::class, 'created_by');
    }

    /**
     * Get the admin who reviewed this pending account
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(SysUser::class, 'reviewed_by');
    }

    /**
     * Scope for pending accounts
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved accounts
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for rejected accounts
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Check if account is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if account is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if account is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Approve the pending account and create actual user
     */
    public function approve(int $reviewerId, ?string $notes = null): ?SysUser
    {
        // Update pending account status
        $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
        ]);

        // Create the actual user account
        $user = SysUser::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password, // Already hashed
            'role' => $this->role,
            'employee_id' => $this->employee_id,
            'department' => $this->department,
            'is_active' => $this->is_active,
        ]);

        return $user;
    }

    /**
     * Reject the pending account
     */
    public function reject(int $reviewerId, ?string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
        ]);
    }

    /**
     * Get the role display name
     */
    public function getRoleDisplayName(): string
    {
        return match($this->role) {
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_SSD => 'SSD Personnel',
            self::ROLE_SECURITY => 'Security Personnel',
            self::ROLE_USER => 'User',
            default => ucfirst($this->role),
        };
    }

    /**
     * Get the status display name
     */
    public function getStatusDisplayName(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
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
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'secondary',
        };
    }
}
