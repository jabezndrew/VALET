<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RfidTag;
use App\Models\GuestAccess;
use App\Models\ParkingEntry;
use App\Models\RfidScanLog;
use App\Services\ExpoPushNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class RfidController extends Controller
{
    // Verify RFID at entrance - Opens servo for 7 seconds if valid
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

                // Log scan event
                RfidScanLog::create([
                    'uid' => $uid,
                    'status' => 'invalid',
                    'message' => 'RFID not registered',
                    'scan_type' => 'entry',
                    'gate_mac' => $gateMac,
                ]);

                // Send push notification to security
                ExpoPushNotificationService::sendRfidAlert('invalid', [
                    'uid' => $uid,
                    'gate_mac' => $gateMac
                ]);

                // Web bell notification
                $webNotifs = Cache::get('admin_override_notifications', []);
                $webNotifs[] = [
                    'id'          => uniqid(),
                    'type'        => 'rfid_alert',
                    'alert_type'  => 'invalid',
                    'uid'         => $uid,
                    'user_name'   => 'N/A',
                    'vehicle_plate' => 'N/A',
                    'message'     => 'RFID not registered',
                    'created_at'  => now()->toISOString(),
                ];
                Cache::put('admin_override_notifications', array_slice($webNotifs, -50), now()->addDays(7));

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

                // Log scan event
                RfidScanLog::create([
                    'uid' => $uid,
                    'status' => 'expired',
                    'message' => 'RFID expired',
                    'scan_type' => 'entry',
                    'gate_mac' => $gateMac,
                    'user_name' => $rfidTag->user->name ?? 'Unknown',
                    'vehicle_plate' => $rfidTag->vehicle->plate_number ?? 'N/A',
                ]);

                // Send push notification to security
                ExpoPushNotificationService::sendRfidAlert('expired', [
                    'uid' => $uid,
                    'user_name' => $rfidTag->user->name ?? 'Unknown',
                    'vehicle_plate' => $rfidTag->vehicle->plate_number ?? 'N/A',
                    'gate_mac' => $gateMac
                ]);

                // Web bell notification
                $webNotifs = Cache::get('admin_override_notifications', []);
                $webNotifs[] = [
                    'id'            => uniqid(),
                    'type'          => 'rfid_alert',
                    'alert_type'    => 'expired',
                    'uid'           => $uid,
                    'user_name'     => $rfidTag->user->name ?? 'Unknown',
                    'vehicle_plate' => $rfidTag->vehicle->plate_number ?? 'N/A',
                    'message'       => 'RFID expired',
                    'created_at'    => now()->toISOString(),
                ];
                Cache::put('admin_override_notifications', array_slice($webNotifs, -50), now()->addDays(7));

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

                // Log scan event
                RfidScanLog::create([
                    'uid' => $uid,
                    'status' => $rfidTag->status,
                    'message' => 'RFID ' . $rfidTag->status,
                    'scan_type' => 'entry',
                    'gate_mac' => $gateMac,
                    'user_name' => $rfidTag->user->name ?? 'Unknown',
                    'vehicle_plate' => $rfidTag->vehicle->plate_number ?? 'N/A',
                ]);

                // Send push notification to security
                ExpoPushNotificationService::sendRfidAlert($rfidTag->status, [
                    'uid' => $uid,
                    'user_name' => $rfidTag->user->name ?? 'Unknown',
                    'vehicle_plate' => $rfidTag->vehicle->plate_number ?? 'N/A',
                    'gate_mac' => $gateMac
                ]);

                // Web bell notification
                $webNotifs = Cache::get('admin_override_notifications', []);
                $webNotifs[] = [
                    'id'            => uniqid(),
                    'type'          => 'rfid_alert',
                    'alert_type'    => $rfidTag->status,
                    'uid'           => $uid,
                    'user_name'     => $rfidTag->user->name ?? 'Unknown',
                    'vehicle_plate' => $rfidTag->vehicle->plate_number ?? 'N/A',
                    'message'       => 'RFID ' . $rfidTag->status,
                    'created_at'    => now()->toISOString(),
                ];
                Cache::put('admin_override_notifications', array_slice($webNotifs, -50), now()->addDays(7));

                return response()->json($scanData);
            }

            // Check if linked user is active
            if ($rfidTag->user && !$rfidTag->user->is_active) {
                $scanData = [
                    'uid' => $uid,
                    'valid' => false,
                    'message' => 'Account is disabled. Please go to office.',
                    'user_name' => $rfidTag->user->name ?? 'Unknown',
                    'vehicle_plate' => $rfidTag->vehicle->plate_number ?? 'N/A',
                    'duration' => 10,
                    'scan_time' => now()->timestamp . '.' . now()->micro
                ];

                Cache::put('rfid_scan_latest', $scanData, 15);

                RfidScanLog::create([
                    'uid' => $uid,
                    'status' => 'invalid',
                    'message' => 'Account disabled',
                    'scan_type' => 'entry',
                    'gate_mac' => $gateMac,
                    'user_name' => $rfidTag->user->name ?? 'Unknown',
                    'vehicle_plate' => $rfidTag->vehicle->plate_number ?? 'N/A',
                ]);

                return response()->json($scanData);
            }

            // Check if linked vehicle is valid (active, not expired, not disabled)
            if ($rfidTag->vehicle && !$rfidTag->vehicle->isValid()) {
                $vehicle = $rfidTag->vehicle;
                $reason = !$vehicle->is_active
                    ? 'Vehicle registration is disabled.'
                    : 'Vehicle registration has expired.';

                $scanData = [
                    'uid' => $uid,
                    'valid' => false,
                    'message' => $reason,
                    'user_name' => $rfidTag->user->name ?? 'Unknown',
                    'vehicle_plate' => $vehicle->plate_number ?? 'N/A',
                    'duration' => 10,
                    'scan_time' => now()->timestamp . '.' . now()->micro
                ];

                Cache::put('rfid_scan_latest', $scanData, 15);

                RfidScanLog::create([
                    'uid' => $uid,
                    'status' => 'invalid',
                    'message' => $reason,
                    'scan_type' => 'entry',
                    'gate_mac' => $gateMac,
                    'user_name' => $rfidTag->user->name ?? 'Unknown',
                    'vehicle_plate' => $vehicle->plate_number ?? 'N/A',
                ]);

                return response()->json($scanData);
            }

            // Check if vehicle is already inside
            $activeEntry = ParkingEntry::where('rfid_tag_id', $rfidTag->id)
                ->whereIn('status', ['entered', 'parked'])
                ->exists();

            if ($activeEntry) {
                $scanData = [
                    'uid' => $uid,
                    'valid' => false,
                    'message' => 'Vehicle is already inside.',
                    'user_name' => $rfidTag->user->name ?? 'Unknown',
                    'vehicle_plate' => $rfidTag->vehicle->plate_number ?? 'N/A',
                    'duration' => 10,
                    'scan_time' => now()->timestamp . '.' . now()->micro
                ];

                Cache::put('rfid_scan_latest', $scanData, 15);

                RfidScanLog::create([
                    'uid' => $uid,
                    'status' => 'invalid',
                    'message' => 'Vehicle already inside',
                    'scan_type' => 'entry',
                    'gate_mac' => $gateMac,
                    'user_name' => $rfidTag->user->name ?? 'Unknown',
                    'vehicle_plate' => $rfidTag->vehicle->plate_number ?? 'N/A',
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
                'status' => 'entered',
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

            // Log scan event
            RfidScanLog::create([
                'uid' => $uid,
                'status' => 'valid',
                'message' => 'Access granted',
                'scan_type' => 'entry',
                'gate_mac' => $gateMac,
                'user_name' => $rfidTag->user->name ?? 'Unknown',
                'vehicle_plate' => $rfidTag->vehicle->plate_number ?? 'N/A',
            ]);

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

    // Log RFID at exit - No servo control, just logging
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
            $rfidTag = RfidTag::where('uid', $uid)->with(['user', 'vehicle'])->first();

            if (!$rfidTag) {
                return response()->json([
                    'success' => false,
                    'message' => 'RFID not found'
                ]);
            }

            // Find latest entry for this user that hasn't exited
            $entry = ParkingEntry::where('rfid_tag_id', $rfidTag->id)
                ->whereIn('status', ['entered', 'parked'])
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

                // Log scan event
                RfidScanLog::create([
                    'uid' => $uid,
                    'status' => 'valid',
                    'message' => 'Exit logged',
                    'scan_type' => 'exit',
                    'gate_mac' => $gateMac,
                    'user_name' => $rfidTag->user->name ?? 'Unknown',
                    'vehicle_plate' => $rfidTag->vehicle->plate_number ?? 'N/A',
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

    // Manual servo open - Admin/Security/SSD only
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

    // Verify guest access ID
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

    // Mark entry as parked - called when user taps "I've Parked"
    public function markParked(Request $request)
    {
        $request->validate([
            'uid' => 'required|string',
        ]);

        $uid = strtoupper($request->uid);

        $rfidTag = RfidTag::where('uid', $uid)->first();

        if (!$rfidTag) {
            return response()->json(['success' => false, 'message' => 'RFID tag not found.'], 404);
        }

        $entry = ParkingEntry::where('rfid_tag_id', $rfidTag->id)
            ->where('status', 'entered')
            ->orderBy('entry_time', 'desc')
            ->first();

        if (!$entry) {
            return response()->json(['success' => false, 'message' => 'No active entry found for this RFID.'], 404);
        }

        $entry->update(['status' => 'parked']);

        return response()->json(['success' => true, 'message' => 'Status updated to parked.']);
    }

    // Get all RFID tags with linked user and vehicle
    public function tags(Request $request)
    {
        $tags = RfidTag::with(['user', 'vehicle'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($tag) => [
                'id'            => $tag->id,
                'uid'           => $tag->uid,
                'status'        => $tag->status,
                'expiry_date'   => $tag->expiry_date,
                'user_name'     => $tag->user->name ?? null,
                'user_role'     => $tag->user->role ?? null,
                'vehicle_plate' => $tag->vehicle->plate_number ?? null,
                'vehicle_make'  => $tag->vehicle->vehicle_make ?? null,
                'vehicle_model' => $tag->vehicle->vehicle_model ?? null,
                'created_at'    => $tag->created_at->toISOString(),
            ]);

        return response()->json(['tags' => $tags, 'count' => $tags->count()]);
    }

    // Get recent RFID scan events (for mobile polling)
    public function recentScans(Request $request)
    {
        $minutes = (int) $request->query('minutes', 5);
        $minutes = max(1, min($minutes, 60));

        $scans = RfidScanLog::where('created_at', '>=', now()->subMinutes($minutes))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($scan) {
                return [
                    'id' => $scan->id,
                    'uid' => $scan->uid,
                    'status' => $scan->status,
                    'message' => $scan->message,
                    'scan_type' => $scan->scan_type,
                    'gate_mac' => $scan->gate_mac,
                    'user_name' => $scan->user_name,
                    'vehicle_plate' => $scan->vehicle_plate,
                    'timestamp' => $scan->created_at->toISOString(),
                ];
            });

        return response()->json([
            'scans' => $scans,
            'count' => $scans->count(),
            'from' => now()->subMinutes($minutes)->toISOString(),
            'to' => now()->toISOString(),
        ]);
    }

    // Get vehicles parked for more than 12 hours
    public function longParked()
    {
        $threshold = Carbon::now()->subHours(12);

        $vehicles = ParkingEntry::where('status', 'parked')
            ->where('entry_time', '<', $threshold)
            ->with(['user', 'rfidTag.vehicle'])
            ->orderBy('entry_time', 'asc')
            ->get()
            ->map(fn($entry) => [
                'plate'        => $entry->vehicle_plate,
                'owner'        => $entry->user->name ?? 'Unknown',
                'parked_since' => Carbon::parse($entry->entry_time)->toISOString(),
                'hours_parked' => round(Carbon::parse($entry->entry_time)->diffInMinutes(now()) / 60, 1),
            ]);

        return response()->json([
            'long_parked' => $vehicles,
            'count'       => $vehicles->count(),
            'threshold_hours' => 12,
        ]);
    }

    public function parkedUsers()
    {
        $entries = ParkingEntry::whereIn('status', ['parked', 'entered'])
            ->with(['user', 'rfidTag.vehicle'])
            ->orderBy('entry_time', 'desc')
            ->get()
            ->map(fn($entry) => [
                'id'           => $entry->id,
                'name'         => $entry->user->name ?? 'Guest',
                'plate'        => $entry->vehicle_plate ?? 'N/A',
                'entry_type'   => $entry->entry_type,
                'status'       => $entry->status,
                'entry_time'   => Carbon::parse($entry->entry_time)->toISOString(),
                'minutes_parked' => Carbon::parse($entry->entry_time)->diffInMinutes(now()),
            ]);

        return response()->json([
            'parked'  => $entries,
            'count'   => $entries->count(),
        ]);
    }

    // Lookup-only vehicle verification for mobile staff (no side effects)
    // POST /public/verify-vehicle
    // Body: { mode: 'rfid'|'plate', value: string }
    public function lookupVehicle(Request $request)
    {
        $request->validate([
            'mode'  => 'required|in:rfid,plate',
            'value' => 'required|string|max:100',
        ]);

        $mode  = $request->mode;
        $value = strtoupper(trim($request->value));

        if ($mode === 'rfid') {
            $rfidTag = RfidTag::where('uid', $value)->with(['user', 'vehicle'])->first();

            if (!$rfidTag) {
                return response()->json(['found' => false, 'message' => 'RFID tag not found in system.']);
            }

            $status = $rfidTag->status;
            $expired = $rfidTag->expiry_date && Carbon::parse($rfidTag->expiry_date)->isPast();

            return response()->json([
                'found'   => true,
                'valid'   => $status === 'active' && !$expired,
                'status'  => $expired ? 'expired' : $status,
                'message' => $expired
                    ? 'RFID tag expired on ' . Carbon::parse($rfidTag->expiry_date)->format('M j, Y')
                    : ($status === 'active' ? 'RFID tag is active.' : 'RFID tag is ' . $status . '.'),
                'uid'           => $rfidTag->uid,
                'user_name'     => $rfidTag->user->name ?? null,
                'user_role'     => $rfidTag->user->role ?? null,
                'vehicle_plate' => $rfidTag->vehicle->plate_number ?? null,
                'vehicle_make'  => $rfidTag->vehicle->vehicle_make ?? null,
                'vehicle_model' => $rfidTag->vehicle->vehicle_model ?? null,
                'expiry_date'   => $rfidTag->expiry_date,
            ]);
        }

        // Plate lookup
        $vehicle = \App\Models\Vehicle::with('owner')->where('plate_number', $value)->first();

        if (!$vehicle) {
            return response()->json(['found' => false, 'message' => 'No registered vehicle found with that plate number.']);
        }

        return response()->json([
            'found'         => true,
            'valid'         => true,
            'status'        => 'registered',
            'message'       => 'Vehicle is registered.',
            'vehicle_plate' => $vehicle->plate_number,
            'vehicle_make'  => $vehicle->vehicle_make ?? null,
            'vehicle_model' => $vehicle->vehicle_model ?? null,
            'user_name'     => $vehicle->owner->name ?? null,
            'user_role'     => $vehicle->owner->role ?? null,
        ]);
    }
}
