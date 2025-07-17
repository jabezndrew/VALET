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
            $status = $request->get('status'); // active, inactive, all

            $query = SysUser::query();

            // Search by name, email, or employee_id
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('employee_id', 'like', "%{$search}%");
                });
            }

            // Filter by role
            if ($role && $role !== 'all') {
                $query->where('role', $role);
            }

            // Filter by status
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }

            // Get users
            $users = $query->select([
                'id',
                'name', 
                'email',
                'role',
                'employee_id',
                'department',
                'is_active',
                'created_at'
            ])->latest()->get();

            // Transform the data to include role display name
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
                'users' => $transformedUsers
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve users',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user statistics
     */
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
                'recent_registrations' => SysUser::where('created_at', '>=', now()->subDays(30))->count(),
                'by_department' => SysUser::whereNotNull('department')
                    ->groupBy('department')
                    ->selectRaw('department, count(*) as count')
                    ->pluck('count', 'department')
                    ->toArray(),
            ];

            return response()->json($stats);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve statistics',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}