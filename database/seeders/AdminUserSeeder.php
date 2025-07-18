<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\SysUser;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Only create if no admin exists
        if (SysUser::where('role', 'admin')->count() === 0) {
            SysUser::create([
                'name' => 'VALET Administrator',
                'email' => 'admin@valet.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'employee_id' => 'ADMIN001',
                'department' => 'IT Administration',
                'is_active' => true,
            ]);
        }
        if (SysUser::where('role', 'user')->count() === 0) {
            SysUser::create([
                'name' => 'John Doe',
                'email' => 'user@valet.com',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'employee_id' => 'USER001',
                'department' => 'General User',
                'is_active' => true,
            ]);
        }
    }
}