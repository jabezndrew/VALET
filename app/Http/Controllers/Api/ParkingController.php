<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class ParkingController extends Controller
{
    /**
     * Get all parking spaces
     */
    public function index(): JsonResponse
    {
        try {
            $spaces = DB::table('parking_spaces')
                ->orderBy('sensor_id')
                ->get();

            return response()->json($spaces);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch parking data'], 500);
        }
    }

    /**
     * Store/Update parking space data from ESP32
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate incoming data
            $validated = $request->validate([
                'sensor_id' => 'required|integer',
                'is_occupied' => 'required|boolean',
                'distance_cm' => 'required|integer|min:0'
            ]);

            // Insert or update parking space
            DB::table('parking_spaces')->updateOrInsert(
                ['sensor_id' => $validated['sensor_id']],
                [
                    'is_occupied' => $validated['is_occupied'],
                    'distance_cm' => $validated['distance_cm'],
                    'updated_at' => now()
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Parking data updated successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Invalid data provided',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update parking data'
            ], 500);
        }
    }

    /**
     * Get statistics about parking spaces
     */
    public function stats(): JsonResponse
    {
        try {
            $total = DB::table('parking_spaces')->count();
            $occupied = DB::table('parking_spaces')->where('is_occupied', true)->count();
            $available = $total - $occupied;

            return response()->json([
                'total' => $total,
                'occupied' => $occupied,
                'available' => $available,
                'occupancy_rate' => $total > 0 ? round(($occupied / $total) * 100, 2) : 0
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch statistics'], 500);
        }
    }
}