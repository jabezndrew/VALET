<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class SysUser extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'sys_users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'employee_id',
        'department',
        'expo_push_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // Query Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    // Role checking methods
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSSD(): bool
    {
        return in_array($this->role, ['ssd', 'admin']);
    }

    public function isSecurity(): bool
    {
        return in_array($this->role, ['security', 'ssd', 'admin']);
    }

    public function canViewCars(): bool
    {
        return $this->isSecurity();
    }

    public function canManageCars(): bool
    {
        return $this->isSSD();
    }

    public function canManageUsers(): bool
    {
        return $this->isSSD();
    }

    public function canApprovePendingAccounts(): bool
    {
        return $this->isAdmin();
    }

    public function getRoleDisplayName(): string
    {
        return match($this->role) {
            'admin' => 'Administrator',
            'ssd' => 'SSD Personnel',
            'security' => 'Security Personnel',
            'user' => 'User',
            default => 'Unknown'
        };
    }

    public function getRoleBadgeClass(): string
    {
        return match($this->role) {
            'admin' => 'bg-danger',
            'ssd' => 'bg-primary',
            'security' => 'bg-warning',
            'user' => 'bg-secondary',
            default => 'bg-light'
        };
    }

    // Relationships

    /**
     * Get the vehicles owned by this user
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'owner_id');
    }

    /**
     * Get the feedbacks created by this user
     */
    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class, 'user_id');
    }

    /**
     * Get the feedbacks responded to by this admin
     */
    public function respondedFeedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class, 'admin_id');
    }

    /**
     * Get the pending accounts created by this user
     */
    public function createdPendingAccounts(): HasMany
    {
        return $this->hasMany(PendingAccount::class, 'created_by');
    }

    /**
     * Get the pending accounts reviewed by this admin
     */
    public function reviewedPendingAccounts(): HasMany
    {
        return $this->hasMany(PendingAccount::class, 'reviewed_by');
    }
}