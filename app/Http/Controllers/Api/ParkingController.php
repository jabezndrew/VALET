<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParkingSpace;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ParkingController extends Controller
{
    /**
     * Get all parking spaces
     */
    public function index(): JsonResponse
    {
        try {
            $spaces = ParkingSpace::orderBy('sensor_id')->get();

            return response()->json($spaces);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch parking data', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Store/Update parking space data from ESP32
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Support both old (sensor_id) and new (space_code or floor/column/slot) formats
            $validated = $request->validate([
                'sensor_id' => 'sometimes|integer',
                'space_code' => 'sometimes|string|max:10',
                'floor_number' => 'sometimes|integer',
                'column_code' => 'sometimes|string|max:5',
                'slot_number' => 'sometimes|integer',
                'is_occupied' => 'required|boolean',
                'distance_cm' => 'required|integer|min:0',
                'floor_level' => 'sometimes|string|max:255'
            ]);

            // Parse space_code if provided
            if (isset($validated['space_code'])) {
                $parsed = ParkingSpace::parseSpaceCode($validated['space_code']);
                if ($parsed) {
                    $validated['floor_number'] = $parsed['floor_number'];
                    $validated['column_code'] = $parsed['column_code'];
                    $validated['slot_number'] = $parsed['slot_number'];
                }
            }

            // Build space_code if floor/column/slot provided
            if (isset($validated['floor_number']) && isset($validated['column_code']) && isset($validated['slot_number'])) {
                $validated['space_code'] = ParkingSpace::buildSpaceCode(
                    $validated['floor_number'],
                    $validated['column_code'],
                    $validated['slot_number']
                );
            }

            // Set default floor level if not provided
            $validated['floor_level'] = $validated['floor_level'] ?? '4th Floor';

            // Determine unique identifier (prefer space_code, fallback to sensor_id)
            $uniqueField = isset($validated['space_code']) && $validated['space_code']
                ? ['space_code' => $validated['space_code']]
                : ['sensor_id' => $validated['sensor_id'] ?? null];

            if (!$uniqueField['space_code'] && !$uniqueField['sensor_id']) {
                return response()->json([
                    'error' => 'Either space_code or sensor_id must be provided'
                ], 422);
            }

            // Insert or update parking space using Eloquent
            $space = ParkingSpace::updateOrCreate($uniqueField, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Parking data updated successfully',
                'data' => [
                    'sensor_id' => $space->sensor_id,
                    'space_code' => $space->space_code,
                    'floor_number' => $space->floor_number,
                    'column_code' => $space->column_code,
                    'slot_number' => $space->slot_number,
                    'floor_level' => $space->floor_level,
                    'is_occupied' => $space->is_occupied,
                    'distance_cm' => $space->distance_cm
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Invalid data provided',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update parking data',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics about parking spaces (with floor breakdown)
     */
    public function stats(): JsonResponse
    {
        try {
            $total = ParkingSpace::count();
            $occupied = ParkingSpace::occupied()->count();
            $available = $total - $occupied;

            // Get stats by floor using Eloquent query builder
            $floorStats = ParkingSpace::selectRaw('floor_level, COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN is_occupied = 1 THEN 1 ELSE 0 END) as occupied')
                ->selectRaw('SUM(CASE WHEN is_occupied = 0 THEN 1 ELSE 0 END) as available')
                ->groupBy('floor_level')
                ->get();

            return response()->json([
                'overall' => [
                    'total' => $total,
                    'occupied' => $occupied,
                    'available' => $available,
                    'occupancy_rate' => $total > 0 ? round(($occupied / $total) * 100, 2) : 0
                ],
                'by_floor' => $floorStats
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch statistics'], 500);
        }
    }

    /**
     * Get parking spaces for a specific floor
     */
    public function getByFloor($floorLevel): JsonResponse
    {
        try {
            $spaces = ParkingSpace::forFloor($floorLevel)->get();

            return response()->json([
                'floor_level' => $floorLevel,
                'spaces' => $spaces,
                'count' => $spaces->count()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch floor data'], 500);
        }
    }
}