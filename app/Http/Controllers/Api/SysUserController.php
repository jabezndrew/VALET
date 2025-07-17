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
            $perPage = $request->get('per_page', 50); // Default 50 users

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

            // Get users with pagination
            $users = $query->select([
                'id',
                'name', 
                'email',
                'role',
                'employee_id',
                'department',
                'is_active',
                'created_at'
            ])->latest()->paginate($perPage);

            // Transform the data to include role display name
            $transformedUsers = $users->getCollection()->map(function ($user) {
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
                'message' => 'Users retrieved successfully',
                'data' => [
                    'users' => $transformedUsers,
                    'pagination' => [
                        'current_page' => $users->currentPage(),
                        'last_page' => $users->lastPage(),
                        'per_page' => $users->perPage(),
                        'total' => $users->total(),
                        'from' => $users->firstItem(),
                        'to' => $users->lastItem(),
                    ],
                    'filters' => [
                        'search' => $search,
                        'role' => $role,
                        'status' => $status,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users',
                'error' => $e->getMessage()
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

            return response()->json([
                'success' => true,
                'message' => 'Statistics retrieved successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}