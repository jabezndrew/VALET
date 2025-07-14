<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
}