<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class FeedbackController extends Controller
{
    /**
     * Get all feedbacks (with filters)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Feedback::with(['user:id,name,role,email']);

            // Apply filters
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('type') && $request->type !== 'all') {
                $query->where('type', $request->type);
            }

            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('message', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);

            $total = $query->count();
            $feedbacks = $query->orderBy('created_at', 'desc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get()
                ->map(function ($feedback) {
                    // Flatten user data for backward compatibility
                    return [
                        'id' => $feedback->id,
                        'user_id' => $feedback->user_id,
                        'type' => $feedback->type,
                        'message' => $feedback->message,
                        'rating' => $feedback->rating,
                        'email' => $feedback->email,
                        'issues' => $feedback->issues,
                        'device_info' => $feedback->device_info,
                        'status' => $feedback->status,
                        'admin_response' => $feedback->admin_response,
                        'admin_id' => $feedback->admin_id,
                        'responded_at' => $feedback->responded_at,
                        'created_at' => $feedback->created_at,
                        'updated_at' => $feedback->updated_at,
                        'user_name' => $feedback->user->name,
                        'user_role' => $feedback->user->role,
                        'user_email' => $feedback->user->email,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $feedbacks,
                'pagination' => [
                    'current_page' => (int) $page,
                    'per_page' => (int) $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage),
                    'has_more' => ($page * $perPage) < $total,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve feedbacks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single feedback by ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $feedback = Feedback::with(['user:id,name,role,email'])->find($id);

            if (!$feedback) {
                return response()->json([
                    'success' => false,
                    'message' => 'Feedback not found'
                ], 404);
            }

            // Flatten user data for backward compatibility
            $data = [
                'id' => $feedback->id,
                'user_id' => $feedback->user_id,
                'type' => $feedback->type,
                'message' => $feedback->message,
                'rating' => $feedback->rating,
                'email' => $feedback->email,
                'issues' => $feedback->issues,
                'device_info' => $feedback->device_info,
                'status' => $feedback->status,
                'admin_response' => $feedback->admin_response,
                'admin_id' => $feedback->admin_id,
                'responded_at' => $feedback->responded_at,
                'created_at' => $feedback->created_at,
                'updated_at' => $feedback->updated_at,
                'user_name' => $feedback->user->name,
                'user_role' => $feedback->user->role,
                'user_email' => $feedback->user->email,
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve feedback',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new feedback
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:sys_users,id',
                'type' => 'required|in:general,bug,feature,parking',
                'message' => 'required|string|max:2000',
                'rating' => 'nullable|integer|min:1|max:5',
                'email' => 'nullable|email|max:255',
                'issues' => 'nullable|array',
                'device_info' => 'nullable|array',
            ]);

            // Only save rating for general feedback
            $validated['rating'] = ($validated['type'] === 'general') ? ($validated['rating'] ?? null) : null;

            // Create feedback using Eloquent
            $feedback = Feedback::create($validated);

            // Load user relationship
            $feedback->load(['user:id,name,role']);

            // Flatten user data for backward compatibility
            $data = array_merge($feedback->toArray(), [
                'user_name' => $feedback->user->name,
                'user_role' => $feedback->user->role,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Feedback created successfully',
                'data' => $data
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create feedback',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update feedback (admin response)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $feedback = Feedback::find($id);

            if (!$feedback) {
                return response()->json([
                    'success' => false,
                    'message' => 'Feedback not found'
                ], 404);
            }

            $validated = $request->validate([
                'status' => 'sometimes|in:pending,reviewed,resolved',
                'admin_response' => 'nullable|string|max:1000',
            ]);

            $updateData = [];

            if (isset($validated['status'])) {
                $updateData['status'] = $validated['status'];
            }

            if (isset($validated['admin_response'])) {
                $updateData['admin_response'] = $validated['admin_response'];
                $updateData['admin_id'] = $request->user()->id;
                $updateData['responded_at'] = now();
            }

            $feedback->update($updateData);

            // Load user relationship
            $feedback->load(['user:id,name,role']);

            // Flatten user data for backward compatibility
            $data = array_merge($feedback->toArray(), [
                'user_name' => $feedback->user->name,
                'user_role' => $feedback->user->role,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Feedback updated successfully',
                'data' => $data
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
                'message' => 'Failed to update feedback',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete feedback
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $feedback = Feedback::find($id);

            if (!$feedback) {
                return response()->json([
                    'success' => false,
                    'message' => 'Feedback not found'
                ], 404);
            }

            $feedback->delete();

            return response()->json([
                'success' => true,
                'message' => 'Feedback deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete feedback',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get feedback statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total' => Feedback::count(),
                'pending' => Feedback::pending()->count(),
                'reviewed' => Feedback::reviewed()->count(),
                'resolved' => Feedback::resolved()->count(),
                'by_type' => Feedback::query()
                    ->selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray(),
                'recent_30_days' => Feedback::recent(30)->count(),
                'average_rating' => Feedback::whereNotNull('rating')->avg('rating'),
            ];

            return response()->json([
                'success' => true,
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

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'feedback_ids' => 'required|array|min:1',
                'feedback_ids.*' => 'integer|exists:feedbacks,id',
                'status' => 'required|in:pending,reviewed,resolved',
            ]);

            $updated = Feedback::whereIn('id', $validated['feedback_ids'])
                ->update(['status' => $validated['status']]);

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$updated} feedback(s)",
                'updated_count' => $updated
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
                'message' => 'Failed to bulk update',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}