<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SysUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SysUserController extends Controller
{
    /**
     * Get all registered users
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $search = $request->get('search');
            $role = $request->get('role');
            $status = $request->get('status');

            $query = SysUser::query();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('employee_id', 'like', "%{$search}%");
                });
            }

            if ($role && $role !== 'all') {
                $query->where('role', $role);
            }

            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }

            $users = $query->select([
                'id', 'name', 'email', 'role', 'employee_id', 
                'department', 'is_active', 'created_at'
            ])->latest()->get();

            $transformedUsers = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'role_display' => $user->getRoleDisplayName(),
                    'employee_id' => $user->employee_id,
                    'department' => $user->department,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'users' => $transformedUsers
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users'
            ], 500);
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total_users' => SysUser::count(),
                'active_users' => SysUser::where('is_active', true)->count(),
                'inactive_users' => SysUser::where('is_active', false)->count(),
                'by_role' => [
                    'admin' => SysUser::where('role', 'admin')->count(),
                    'ssd' => SysUser::where('role', 'ssd')->count(),
                    'security' => SysUser::where('role', 'security')->count(),
                    'user' => SysUser::where('role', 'user')->count(),
                ],
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics'
            ], 500);
        }
    }
}