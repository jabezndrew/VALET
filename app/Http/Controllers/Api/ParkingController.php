<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\ParkingSpace;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ParkingController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $spaces = ParkingSpace::orderBy('sensor_id')->get();

            return response()->json($spaces);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch parking data', 'details' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'sensor_id' => 'required|integer',
                'is_occupied' => 'required|boolean',
                'distance_cm' => 'required|integer|min:0',
                'floor_level' => 'sometimes|string|max:255'
            ]);

            $validated['floor_level'] = $validated['floor_level'] ?? '4th Floor';

            $space = ParkingSpace::updateOrCreate(
                ['sensor_id' => $validated['sensor_id']],
                $validated
            );

            return response()->json([
                'success' => true,
                'message' => 'Parking data updated successfully',
                'data' => [
                    'sensor_id' => $space->sensor_id,
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

    public function stats(): JsonResponse
    {
        try {
            $total = ParkingSpace::count();
            $occupied = ParkingSpace::occupied()->count();
            $available = $total - $occupied;

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
