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
        if (SysUser::where('role', 'ssd')->count() === 0) {
            SysUser::create([
                'name' => 'SSD User',
                'email' => 'ssd@valet.com',
                'password' => Hash::make('password123'),
                'role' => 'ssd',
                'employee_id' => 'SSD001',
                'department' => 'SSD Department',
                'is_active' => true,
            ]);
        }
        if (SysUser::where('role', 'security')->count() === 0) {
            SysUser::create([
                'name' => 'Ahh Chip',
                'email' => 'security@valet.com',
                'password' => Hash::make('password123'),
                'role' => 'security',
                'employee_id' => 'SECURITY001',
                'department' => 'Security Department',
                'is_active' => true,
            ]);
        }
    }
}