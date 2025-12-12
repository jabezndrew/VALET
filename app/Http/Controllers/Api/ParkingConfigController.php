<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParkingFloor;
use App\Models\ParkingColumn;
use App\Models\ParkingConfig;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ParkingConfigController extends Controller
{
    public function getFloors(): JsonResponse
    {
        try {
            $floors = ParkingFloor::active()->ordered()->get();
            return response()->json($floors);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch floors'], 500);
        }
    }

    public function getColumns(int $floorNumber): JsonResponse
    {
        try {
            $floor = ParkingFloor::where('floor_number', $floorNumber)->first();

            if (!$floor) {
                return response()->json(['error' => 'Floor not found'], 404);
            }

            $columns = $floor->activeColumns()->get();
            return response()->json($columns);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch columns'], 500);
        }
    }

    public function getMaxSlots(int $floorNumber, string $columnCode): JsonResponse
    {
        try {
            $floor = ParkingFloor::where('floor_number', $floorNumber)->first();

            if (!$floor) {
                return response()->json(['error' => 'Floor not found'], 404);
            }

            $column = $floor->columns()->where('column_code', $columnCode)->first();

            if (!$column) {
                return response()->json(['error' => 'Column not found'], 404);
            }

            $maxSlots = ParkingConfig::get('max_slots_per_column', 20);

            return response()->json([
                'max_slots' => (int) $maxSlots,
                'floor_number' => $floorNumber,
                'column_code' => $columnCode
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch slot configuration'], 500);
        }
    }

    public function createFloor(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'floor_number' => 'required|integer|unique:parking_floors,floor_number',
                'floor_name' => 'required|string|max:50',
                'display_order' => 'sometimes|integer'
            ]);

            $floor = ParkingFloor::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Floor created successfully',
                'data' => $floor
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create floor',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function createColumn(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'floor_number' => 'required|integer|exists:parking_floors,floor_number',
                'column_code' => 'required|string|max:5',
                'column_name' => 'sometimes|string|max:50',
                'display_order' => 'sometimes|integer'
            ]);

            $floor = ParkingFloor::where('floor_number', $validated['floor_number'])->first();

            $column = ParkingColumn::create([
                'floor_id' => $floor->id,
                'column_code' => $validated['column_code'],
                'column_name' => $validated['column_name'] ?? "Column {$validated['column_code']}",
                'display_order' => $validated['display_order'] ?? 0
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Column created successfully',
                'data' => $column
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create column',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
