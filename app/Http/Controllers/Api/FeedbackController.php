<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeedbackController extends Controller
{
    public function __construct()
    {
        $this->ensureFeedbackTableExists();
    }

    /**
     * Get all feedbacks (with filters)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = DB::table('feedbacks')
                ->leftJoin('sys_users', 'feedbacks.user_id', '=', 'sys_users.id')
                ->select(
                    'feedbacks.*',
                    'sys_users.name as user_name',
                    'sys_users.role as user_role',
                    'sys_users.email as user_email'
                );

            // Apply filters
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('feedbacks.status', $request->status);
            }

            if ($request->has('type') && $request->type !== 'all') {
                $query->where('feedbacks.type', $request->type);
            }

            if ($request->has('user_id')) {
                $query->where('feedbacks.user_id', $request->user_id);
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('feedbacks.message', 'like', "%{$search}%")
                      ->orWhere('sys_users.name', 'like', "%{$search}%")
                      ->orWhere('sys_users.email', 'like', "%{$search}%");
                });
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $perPage;

            $total = $query->count();
            $feedbacks = $query->orderBy('feedbacks.created_at', 'desc')
                ->limit($perPage)
                ->offset($offset)
                ->get()
                ->map(function ($feedback) {
                    // Parse JSON fields
                    $feedback->issues = json_decode($feedback->issues ?? '[]', true);
                    $feedback->device_info = json_decode($feedback->device_info ?? '{}', true);
                    return $feedback;
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
            $feedback = DB::table('feedbacks')
                ->leftJoin('sys_users', 'feedbacks.user_id', '=', 'sys_users.id')
                ->select(
                    'feedbacks.*',
                    'sys_users.name as user_name',
                    'sys_users.role as user_role',
                    'sys_users.email as user_email'
                )
                ->where('feedbacks.id', $id)
                ->first();

            if (!$feedback) {
                return response()->json([
                    'success' => false,
                    'message' => 'Feedback not found'
                ], 404);
            }

            // Parse JSON fields
            $feedback->issues = json_decode($feedback->issues ?? '[]', true);
            $feedback->device_info = json_decode($feedback->device_info ?? '{}', true);

            return response()->json([
                'success' => true,
                'data' => $feedback
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
            $ratingToSave = ($validated['type'] === 'general') ? ($validated['rating'] ?? null) : null;

            $feedbackId = DB::table('feedbacks')->insertGetId([
                'user_id' => $validated['user_id'],
                'type' => $validated['type'],
                'message' => $validated['message'],
                'rating' => $ratingToSave,
                'email' => $validated['email'] ?? null,
                'issues' => json_encode($validated['issues'] ?? []),
                'device_info' => json_encode($validated['device_info'] ?? []),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Get the created feedback
            $feedback = DB::table('feedbacks')
                ->leftJoin('sys_users', 'feedbacks.user_id', '=', 'sys_users.id')
                ->select(
                    'feedbacks.*',
                    'sys_users.name as user_name',
                    'sys_users.role as user_role'
                )
                ->where('feedbacks.id', $feedbackId)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Feedback created successfully',
                'data' => $feedback
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
            $feedback = DB::table('feedbacks')->where('id', $id)->first();
            
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

            $updateData = ['updated_at' => now()];

            if (isset($validated['status'])) {
                $updateData['status'] = $validated['status'];
            }

            if (isset($validated['admin_response'])) {
                $updateData['admin_response'] = $validated['admin_response'];
                $updateData['admin_id'] = $request->user()->id;
                $updateData['responded_at'] = now();
            }

            DB::table('feedbacks')->where('id', $id)->update($updateData);

            // Get updated feedback
            $updatedFeedback = DB::table('feedbacks')
                ->leftJoin('sys_users', 'feedbacks.user_id', '=', 'sys_users.id')
                ->select(
                    'feedbacks.*',
                    'sys_users.name as user_name',
                    'sys_users.role as user_role'
                )
                ->where('feedbacks.id', $id)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Feedback updated successfully',
                'data' => $updatedFeedback
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
            $feedback = DB::table('feedbacks')->where('id', $id)->first();
            
            if (!$feedback) {
                return response()->json([
                    'success' => false,
                    'message' => 'Feedback not found'
                ], 404);
            }

            DB::table('feedbacks')->where('id', $id)->delete();

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
                'total' => DB::table('feedbacks')->count(),
                'pending' => DB::table('feedbacks')->where('status', 'pending')->count(),
                'reviewed' => DB::table('feedbacks')->where('status', 'reviewed')->count(),
                'resolved' => DB::table('feedbacks')->where('status', 'resolved')->count(),
                'by_type' => DB::table('feedbacks')
                    ->select('type')
                    ->selectRaw('COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray(),
                'recent_30_days' => DB::table('feedbacks')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->count(),
                'average_rating' => DB::table('feedbacks')
                    ->whereNotNull('rating')
                    ->avg('rating'),
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

            $updated = DB::table('feedbacks')
                ->whereIn('id', $validated['feedback_ids'])
                ->update([
                    'status' => $validated['status'],
                    'updated_at' => now(),
                ]);

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

    /**
     * Ensure feedback table exists
     */
    private function ensureFeedbackTableExists(): void
    {
        DB::statement("CREATE TABLE IF NOT EXISTS feedbacks (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            type ENUM('general', 'bug', 'feature', 'parking') NOT NULL,
            message TEXT NOT NULL,
            rating INT NULL,
            email VARCHAR(255) NULL,
            issues JSON NULL,
            device_info JSON NULL,
            status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
            admin_response TEXT NULL,
            admin_id BIGINT UNSIGNED NULL,
            responded_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_type (type),
            FOREIGN KEY (user_id) REFERENCES sys_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");
    }
}