<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RfidTag;
use App\Models\GuestAccess;
use App\Models\ParkingEntry;
use App\Services\ExpoPushNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class RfidController extends Controller
{
    /**
     * Verify RFID at entrance - Opens servo for 7 seconds if valid
     */
    public function verify(Request $request)
    {
        $request->validate([
            'uid' => 'required|string',
            'gate_mac' => 'required|string'
        ]);

        try {
            $uid = strtoupper($request->uid);
            $gateMac = $request->gate_mac;

            // Check if RFID tag exists and is active
            $rfidTag = RfidTag::where('uid', $uid)
                ->with(['user', 'vehicle'])
                ->first();

            if (!$rfidTag) {
                $scanData = [
                    'uid' => $uid,
                    'valid' => false,
                    'message' => 'RFID not registered. Please go to office.',
                    'user_name' => 'N/A',
                    'vehicle_plate' => 'N/A',
                    'duration' => 10,
                    'scan_time' => now()->timestamp . '.' . now()->micro
                ];

                // Store in cache for real-time monitoring
                Cache::put('rfid_scan_latest', $scanData, 15);

                // Send push notification to security
                ExpoPushNotificationService::sendRfidAlert('invalid', [
                    'uid' => $uid,
                    'gate_mac' => $gateMac
                ]);

                return response()->json($scanData);
            }

            // Check if expired
            if ($rfidTag->status === 'expired' ||
                ($rfidTag->expiry_date && Carbon::parse($rfidTag->expiry_date)->isPast())) {
                $scanData = [
                    'uid' => $uid,
                    'valid' => false,
                    'message' => 'RFID expired. Please go to office.',
                    'user_name' => $rfidTag->user->name ?? 'Unknown',
                    'vehicle_plate' => $rfidTag->vehicle->plate_number ?? 'N/A',
                    'duration' => 10,
                    'scan_time' => now()->timestamp . '.' . now()->micro
                ];

                Cache::put('rfid_scan_latest', $scanData, 15);

                // Send push notification to security
                ExpoPushNotificationService::sendRfidAlert('expired', [
                    'uid' => $uid,
                    'user_name' => $rfidTag->user->name ?? 'Unknown',
                    'vehicle_plate' => $rfidTag->vehicle->plate_number ?? 'N/A',
                    'gate_mac' => $gateMac
                ]);

                return response()->json($scanData);
            }

            // Check if suspended or lost
            if (in_array($rfidTag->status, ['suspended', 'lost'])) {
                $scanData = [
                    'uid' => $uid,
                    'valid' => false,
                    'message' => 'RFID ' . $rfidTag->status . '. Please go to office.',
                    'user_name' => $rfidTag->user->name ?? 'Unknown',
                    'vehicle_plate' => $rfidTag->vehicle->plate_number ?? 'N/A',
                    'duration' => 10,
                    'scan_time' => now()->timestamp . '.' . now()->micro
                ];

                Cache::put('rfid_scan_latest', $scanData, 15);

                // Send push notification to security
                ExpoPushNotificationService::sendRfidAlert($rfidTag->status, [
                    'uid' => $uid,
                    'user_name' => $rfidTag->user->name ?? 'Unknown',
                    'vehicle_plate' => $rfidTag->vehicle->plate_number ?? 'N/A',
                    'gate_mac' => $gateMac
                ]);

                return response()->json($scanData);
            }

            // Valid RFID - Log entry
            ParkingEntry::create([
                'entry_type' => 'rfid',
                'rfid_tag_id' => $rfidTag->id,
                'user_id' => $rfidTag->user_id,
                'vehicle_plate' => $rfidTag->vehicle->plate_number ?? null,
                'entry_time' => Carbon::now(),
                'status' => 'parked',
                'entry_gate_mac' => $gateMac
            ]);

            $scanData = [
                'valid' => true,
                'message' => 'Access granted',
                'duration' => 7, // Open servo for 7 seconds
                'uid' => $uid,
                'user_name' => $rfidTag->user->name ?? 'Unknown',
                'vehicle_plate' => $rfidTag->vehicle->plate_number ?? 'N/A',
                'scan_time' => now()->timestamp . '.' . now()->micro,
                'user' => [
                    'name' => $rfidTag->user->name ?? 'Unknown',
                    'vehicle_plate' => $rfidTag->vehicle->plate_number ?? 'N/A',
                    'entry_time' => Carbon::now()->format('Y-m-d H:i:s')
                ]
            ];

            // Store in cache for real-time monitoring
            Cache::put('rfid_scan_latest', $scanData, 15);

            return response()->json($scanData);

        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'System error. Please contact admin.',
                'error' => $e->getMessage(),
                'duration' => 10
            ], 500);
        }
    }

    /**
     * Log RFID at exit - No servo control, just logging
     */
    public function exit(Request $request)
    {
        $request->validate([
            'uid' => 'required|string',
            'gate_mac' => 'required|string'
        ]);

        try {
            $uid = strtoupper($request->uid);
            $gateMac = $request->gate_mac;

            // Find RFID tag
            $rfidTag = RfidTag::where('uid', $uid)->first();

            if (!$rfidTag) {
                return response()->json([
                    'success' => false,
                    'message' => 'RFID not found'
                ]);
            }

            // Find latest entry for this user that hasn't exited
            $entry = ParkingEntry::where('rfid_tag_id', $rfidTag->id)
                ->where('status', 'parked')
                ->orderBy('entry_time', 'desc')
                ->first();

            if ($entry) {
                $exitTime = Carbon::now();
                $entryTime = Carbon::parse($entry->entry_time);
                $durationMinutes = $entryTime->diffInMinutes($exitTime);

                $entry->update([
                    'exit_time' => $exitTime,
                    'duration_minutes' => $durationMinutes,
                    'status' => 'exited',
                    'exit_gate_mac' => $gateMac
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Exit logged',
                    'duration_minutes' => $durationMinutes
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No active entry found for this RFID'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'System error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manual servo open - Admin/Security/SSD only
     */
    public function manualOpen(Request $request)
    {
        $request->validate([
            'gate_mac' => 'required|string',
            'reason' => 'nullable|string'
        ]);

        try {
            // Log manual opening for audit
            \Log::info('Manual gate open', [
                'gate_mac' => $request->gate_mac,
                'reason' => $request->reason,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Gate opened manually',
                'duration' => 7
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to open gate',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify guest access ID
     */
    public function verifyGuest(Request $request)
    {
        $request->validate([
            'guest_id' => 'required|string',
            'gate_mac' => 'required|string'
        ]);

        try {
            $guestId = strtoupper($request->guest_id);
            $gateMac = $request->gate_mac;

            $guest = GuestAccess::where('guest_id', $guestId)->first();

            if (!$guest) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Guest ID not found',
                    'duration' => 10
                ]);
            }

            // Check if still valid (within 24 hours)
            $now = Carbon::now();
            if ($now->lt(Carbon::parse($guest->valid_from)) ||
                $now->gt(Carbon::parse($guest->valid_until))) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Guest pass expired',
                    'duration' => 10
                ]);
            }

            // Check status
            if ($guest->status !== 'active') {
                return response()->json([
                    'valid' => false,
                    'message' => 'Guest pass ' . $guest->status,
                    'duration' => 10
                ]);
            }

            // Valid guest - Log entry
            ParkingEntry::create([
                'entry_type' => 'guest',
                'guest_access_id' => $guest->id,
                'vehicle_plate' => $guest->vehicle_plate,
                'entry_time' => Carbon::now(),
                'status' => 'parked',
                'entry_gate_mac' => $gateMac
            ]);

            // Mark guest pass as used
            $guest->update(['status' => 'used']);

            return response()->json([
                'valid' => true,
                'message' => 'Guest access granted',
                'duration' => 7,
                'guest' => [
                    'name' => $guest->name,
                    'vehicle_plate' => $guest->vehicle_plate,
                    'purpose' => $guest->purpose,
                    'entry_time' => Carbon::now()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'System error',
                'error' => $e->getMessage(),
                'duration' => 10
            ], 500);
        }
    }
}
