<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingAccount extends Model
{
    use HasFactory;

    protected $table = 'pending_accounts';

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

    protected $casts = [
        'is_active' => 'boolean',
        'created_by' => 'integer',
        'reviewed_by' => 'integer',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    // Role constants
    const ROLE_USER = 'user';
    const ROLE_SECURITY = 'security';
    const ROLE_SSD = 'ssd';
    const ROLE_ADMIN = 'admin';

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(SysUser::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(SysUser::class, 'reviewed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    // Status checks
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    // Approve and create the actual user account
    public function approve(int $reviewerId, ?string $notes = null): ?SysUser
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
        ]);

        return SysUser::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password, // already hashed
            'role' => $this->role,
            'employee_id' => $this->employee_id,
            'department' => $this->department,
            'is_active' => $this->is_active,
        ]);
    }

    public function reject(int $reviewerId, ?string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
        ]);
    }

    // Display helpers
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

    public function getStatusDisplayName(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            default => ucfirst($this->status),
        };
    }

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
