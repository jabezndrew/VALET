<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class ParkingController extends Controller
{
    /**
     * Ensure table exists with floor_level column
     */
    private function ensureTableExists()
    {
        // Create table if it doesn't exist
        DB::statement("CREATE TABLE IF NOT EXISTS parking_spaces (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sensor_id INT UNIQUE NOT NULL,
            is_occupied BOOLEAN NOT NULL DEFAULT FALSE,
            distance_cm INT,
            floor_level VARCHAR(255) DEFAULT '4th Floor',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
        
        // Add floor_level column if it doesn't exist (for existing tables)
        $columnExists = DB::select("SHOW COLUMNS FROM parking_spaces LIKE 'floor_level'");
        if (empty($columnExists)) {
            DB::statement("ALTER TABLE parking_spaces ADD COLUMN floor_level VARCHAR(255) DEFAULT '4th Floor' AFTER distance_cm");
        }
    }

    /**
     * Get all parking spaces
     */
    public function index(): JsonResponse
    {
        try {
            $this->ensureTableExists();
            
            $spaces = DB::table('parking_spaces')
                ->orderBy('sensor_id')
                ->get();

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
            $this->ensureTableExists();
            
            // Validate incoming data with floor_level
            $validated = $request->validate([
                'sensor_id' => 'required|integer',
                'is_occupied' => 'required|boolean',
                'distance_cm' => 'required|integer|min:0',
                'floor_level' => 'sometimes|string|max:255' // Optional, defaults to '4th Floor'
            ]);

            // Set default floor level if not provided
            if (!isset($validated['floor_level'])) {
                $validated['floor_level'] = '4th Floor';
            }

            // Insert or update parking space with floor level
            DB::table('parking_spaces')->updateOrInsert(
                ['sensor_id' => $validated['sensor_id']],
                [
                    'is_occupied' => $validated['is_occupied'],
                    'distance_cm' => $validated['distance_cm'],
                    'floor_level' => $validated['floor_level'],
                    'updated_at' => now()
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Parking data updated successfully',
                'data' => [
                    'sensor_id' => $validated['sensor_id'],
                    'floor_level' => $validated['floor_level'],
                    'is_occupied' => $validated['is_occupied'],
                    'distance_cm' => $validated['distance_cm']
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
            $this->ensureTableExists();
            
            $total = DB::table('parking_spaces')->count();
            $occupied = DB::table('parking_spaces')->where('is_occupied', true)->count();
            $available = $total - $occupied;

            // Get stats by floor
            $floorStats = DB::table('parking_spaces')
                ->select('floor_level')
                ->selectRaw('COUNT(*) as total')
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
            $this->ensureTableExists();
            
            $spaces = DB::table('parking_spaces')
                ->where('floor_level', $floorLevel)
                ->orderBy('sensor_id')
                ->get();

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