<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SensorAssignment;
use App\Models\ParkingSpace;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SensorAssignmentController extends Controller
{
    /**
     * Get all sensors with their assignments
     */
    public function index(): JsonResponse
    {
        try {
            $sensors = SensorAssignment::with('parkingSpace')->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'sensors' => $sensors
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch sensors',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get unassigned sensors
     */
    public function unassigned(): JsonResponse
    {
        try {
            $sensors = SensorAssignment::where('status', 'unassigned')
                ->orWhereNull('space_code')
                ->orderBy('last_seen', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'sensors' => $sensors
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch unassigned sensors',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Register single sensor for an ESP32 on boot
     */
     public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'mac_address' => 'required|string|max:17',
                'firmware_version' => 'sometimes|string|max:20',
                'device_name' => 'sometimes|string|max:100'
            ]);

            $registered = [];

            // Register all 5 sensors for this ESP32
            for ($sensorIndex = 1; $sensorIndex <= 5; $sensorIndex++) {
                $sensor = SensorAssignment::firstOrCreate(
                    [
                        'mac_address' => $validated['mac_address'],
                        'sensor_index' => $sensorIndex
                    ],
                    [
                        'status' => 'unassigned',
                        'firmware_version' => $validated['firmware_version'] ?? null,
                        'device_name' => $validated['device_name'] ?? null,
                        'last_seen' => now()
                    ]
                );

                // Update last seen and firmware version if already exists
                $sensor->update([
                    'last_seen' => now(),
                    'firmware_version' => $validated['firmware_version'] ?? $sensor->firmware_version
                ]);

                $registered[] = [
                    'sensor_index' => $sensor->sensor_index,
                    'status' => $sensor->status,
                    'is_assigned' => $sensor->isAssigned()
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'All 5 sensors registered successfully',
                'mac_address' => $validated['mac_address'],
                'sensors' => $registered
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to register sensors',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Get sensor configuration by MAC address (for Arduino to fetch assignment)
     * Returns all 5 sensors for this ESP32
     */
    public function getAssignment(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'mac_address' => 'required|string|max:17'
            ]);

            // Get all 5 sensors for this ESP32
            $sensors = SensorAssignment::where('mac_address', $validated['mac_address'])
                ->with('parkingSpace')
                ->orderBy('sensor_index')
                ->get();

            // If no sensors registered yet, return not_registered status
            if ($sensors->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'status' => 'not_registered',
                    'message' => 'No sensors registered yet. Will register when you send data.',
                    'mac_address' => $validated['mac_address']
                ], 200);
            }

            // Format sensor data
            $sensorData = $sensors->map(function($sensor) {
                return [
                    'sensor_index' => $sensor->sensor_index,
                    'status' => $sensor->status,
                    'space_code' => $sensor->space_code,
                    'device_name' => $sensor->device_name,
                    'is_assigned' => $sensor->isAssigned(),
                    'identify_mode' => $sensor->identify_mode,
                    'parking_space' => $sensor->parkingSpace
                ];
            });

            return response()->json([
                'success' => true,
                'status' => 'registered',
                'mac_address' => $validated['mac_address'],
                'sensors' => $sensorData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch assignment',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign sensor to a parking space
     */
    public function assign(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'mac_address' => 'required|string|max:17',
                'sensor_index' => 'required|integer|min:1|max:5',
                'space_code' => 'required|string|max:10|exists:parking_spaces,space_code',
                'device_name' => 'sometimes|string|max:100'
            ]);

            $sensor = SensorAssignment::where('mac_address', $validated['mac_address'])
                ->where('sensor_index', $validated['sensor_index'])
                ->first();

            if (!$sensor) {
                return response()->json([
                    'error' => 'Sensor not found'
                ], 404);
            }

            // Check if space is already assigned to another sensor
            $existingAssignment = SensorAssignment::where('space_code', $validated['space_code'])
                ->where(function($query) use ($validated) {
                    $query->where('mac_address', '!=', $validated['mac_address'])
                          ->orWhere('sensor_index', '!=', $validated['sensor_index']);
                })
                ->where('status', 'active')
                ->first();

            if ($existingAssignment) {
                return response()->json([
                    'error' => 'This parking space is already assigned to another sensor',
                    'existing_sensor' => $existingAssignment->getIdentifier()
                ], 422);
            }

            $sensor->update([
                'space_code' => $validated['space_code'],
                'device_name' => $validated['device_name'] ?? $sensor->device_name,
                'status' => 'active'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sensor assigned successfully',
                'sensor' => $sensor->load('parkingSpace')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to assign sensor',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unassign sensor from parking space
     */
    public function unassign(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'mac_address' => 'required|string|max:17|exists:sensor_assignments,mac_address'
            ]);

            $sensor = SensorAssignment::where('mac_address', $validated['mac_address'])->first();

            $sensor->update([
                'space_code' => null,
                'status' => 'unassigned'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sensor unassigned successfully',
                'sensor' => $sensor
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to unassign sensor',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update sensor details
     */
    public function update(Request $request, $macAddress): JsonResponse
    {
        try {
            $sensor = SensorAssignment::where('mac_address', $macAddress)->firstOrFail();

            $validated = $request->validate([
                'device_name' => 'sometimes|string|max:100',
                'notes' => 'sometimes|string',
                'status' => 'sometimes|in:active,inactive,unassigned'
            ]);

            $sensor->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Sensor updated successfully',
                'sensor' => $sensor
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update sensor',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete sensor registration
     */
    public function destroy($macAddress): JsonResponse
    {
        try {
            $sensor = SensorAssignment::where('mac_address', $macAddress)->firstOrFail();
            $sensor->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sensor deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete sensor',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start identify mode for a sensor (blue LED blinking)
     */
    public function startIdentify(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'mac_address' => 'required|string|max:17',
                'sensor_index' => 'required|integer|min:1|max:5'
            ]);

            $sensor = SensorAssignment::where('mac_address', $validated['mac_address'])
                ->where('sensor_index', $validated['sensor_index'])
                ->firstOrFail();

            $sensor->startIdentify();

            return response()->json([
                'success' => true,
                'message' => 'Identify mode started - blue LED should be blinking',
                'sensor' => $sensor
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to start identify mode',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stop identify mode for a sensor
     */
    public function stopIdentify(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'mac_address' => 'required|string|max:17',
                'sensor_index' => 'required|integer|min:1|max:5'
            ]);

            $sensor = SensorAssignment::where('mac_address', $validated['mac_address'])
                ->where('sensor_index', $validated['sensor_index'])
                ->firstOrFail();

            $sensor->stopIdentify();

            return response()->json([
                'success' => true,
                'message' => 'Identify mode stopped',
                'sensor' => $sensor
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to stop identify mode',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
