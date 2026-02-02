<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SysUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login and get API token (ALL ROLES)
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $user = SysUser::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account deactivated'
                ], 401);
            }

            // Delete any existing tokens for this user to avoid duplicates
            $user->tokens()->delete();

            // Create new token with role-based abilities
            $abilities = $this->getAbilitiesForRole($user->role);
            $token = $user->createToken('valet-api', $abilities);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token->plainTextToken,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'role_display' => $user->getRoleDisplayName(),
                    'employee_id' => $user->employee_id,
                    'department' => $user->department,
                    'is_active' => $user->is_active,
                ],
                'abilities' => $abilities,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate token
     */
    public function validate(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Token valid',
            'user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'role' => $request->user()->role,
            ]
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Logged out'
        ]);
    }

    /**
     * Get profile
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'role_display' => $user->getRoleDisplayName(),
                'employee_id' => $user->employee_id,
                'department' => $user->department,
                'is_active' => $user->is_active,
            ]
        ]);
    }

    /**
     * Store/Update user's Expo push token for notifications
     */
    public function updatePushToken(Request $request): JsonResponse
    {
        $request->validate([
            'expo_push_token' => 'required|string'
        ]);

        $user = $request->user();
        $user->update([
            'expo_push_token' => $request->expo_push_token
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Push token updated successfully'
        ]);
    }

    /**
     * Remove user's push token (on logout or disable notifications)
     */
    public function removePushToken(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->update([
            'expo_push_token' => null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Push token removed successfully'
        ]);
    }

    /**
     * Get abilities based on user role
     */
    private function getAbilitiesForRole(string $role): array
    {
        return match($role) {
            'admin' => [
                'parking:view',
                'parking:manage',
                'vehicles:view',
                'vehicles:manage',
                'vehicles:verify',
                'users:view',
                'users:manage',
                'feedbacks:view',
                'feedbacks:manage',
                'feedbacks:respond',
                'accounts:approve',
            ],
            'ssd' => [
                'parking:view',
                'parking:manage',
                'vehicles:view',
                'vehicles:manage',
                'vehicles:verify',
                'users:view',
                'users:manage',
                'feedbacks:view',
                'feedbacks:manage',
            ],
            'security' => [
                'parking:view',
                'vehicles:view',
                'vehicles:verify',
                'feedbacks:view',
            ],
            'user' => [
                'parking:view',
                'feedbacks:create',
                'feedbacks:view-own',
            ],
            default => ['parking:view'],
        };
    }
}