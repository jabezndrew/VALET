<?php

namespace App\Services;

use App\Models\SysUser;
use App\Models\ParkingEntry;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushNotificationService
{
    private const EXPO_PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    // ── 1. Spot Available ─────────────────────────────────────────────────────
    // Triggered when a space transitions occupied → available.
    // Only sent to users who are currently parked/inside.
    public static function sendSpotAvailable(string $spaceCode, string $floorLevel): void
    {
        // Get users currently inside (parked or entered)
        $activeUserIds = ParkingEntry::whereIn('status', ['parked', 'entered'])
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->unique();

        if ($activeUserIds->isEmpty()) return;

        $users = SysUser::whereIn('id', $activeUserIds)
            ->whereNotNull('expo_push_token')
            ->where('is_active', true)
            ->get();

        if ($users->isEmpty()) return;

        $messages = $users->map(fn($user) => [
            'to'        => $user->expo_push_token,
            'sound'     => 'default',
            'title'     => 'Parking Spot Available',
            'body'      => "Spot {$spaceCode} just became available on {$floorLevel}",
            'data'      => [
                'type'        => 'spot_available',
                'space_code'  => $spaceCode,
                'floor_level' => $floorLevel,
                'timestamp'   => now()->toISOString(),
            ],
            'channelId' => 'spot-available',
            'priority'  => 'high',
            'color'     => '#2F623D',
        ])->values()->all();

        self::send($messages);
        Log::info('Spot available notification sent', ['space_code' => $spaceCode, 'recipients' => $users->count()]);
    }

    // ── 2. Floor Update ───────────────────────────────────────────────────────
    // Triggered when a floor's available count changes.
    // Only sent to users currently parked/inside.
    public static function sendFloorUpdate(string $floorLevel, int $available, int $total): void
    {
        $activeUserIds = ParkingEntry::whereIn('status', ['parked', 'entered'])
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->unique();

        if ($activeUserIds->isEmpty()) return;

        $users = SysUser::whereIn('id', $activeUserIds)
            ->whereNotNull('expo_push_token')
            ->where('is_active', true)
            ->get();

        if ($users->isEmpty()) return;

        $messages = $users->map(fn($user) => [
            'to'        => $user->expo_push_token,
            'sound'     => 'default',
            'title'     => 'Floor Update',
            'body'      => "{$floorLevel}: {$available} more spot(s) available ({$available}/{$total} total)",
            'data'      => [
                'type'        => 'floor_update',
                'floor_level' => $floorLevel,
                'available'   => $available,
                'total'       => $total,
                'timestamp'   => now()->toISOString(),
            ],
            'channelId' => 'floor-updates',
            'priority'  => 'default',
        ])->values()->all();

        self::send($messages);
        Log::info('Floor update notification sent', ['floor' => $floorLevel, 'available' => $available]);
    }

    // ── 3. Feedback Reply ─────────────────────────────────────────────────────
    // Triggered when admin saves a response to a user's feedback.
    // Sent only to the feedback owner.
    public static function sendFeedbackReply(int $userId, string $feedbackPreview, string $adminReply): void
    {
        $user = SysUser::find($userId);

        if (!$user || !$user->expo_push_token || !$user->is_active) return;

        $message = [[
            'to'        => $user->expo_push_token,
            'sound'     => 'default',
            'title'     => 'Admin Replied to your Feedback',
            'body'      => $adminReply,
            'data'      => [
                'type'             => 'feedback_reply',
                'feedback_preview' => $feedbackPreview,
                'reply'            => $adminReply,
                'timestamp'        => now()->toISOString(),
            ],
            'channelId' => 'feedback-replies',
            'priority'  => 'high',
        ]];

        self::send($message);
        Log::info('Feedback reply notification sent', ['user_id' => $userId]);
    }

    // ── 4. RFID Alert ─────────────────────────────────────────────────────────
    // Triggered when an invalid/expired/suspended/unknown card is scanned.
    // Sent to security/ssd/admin roles only.
    public static function sendRfidAlert(string $alertType, array $data): void
    {
        $users = SysUser::whereIn('role', ['security', 'ssd', 'admin'])
            ->whereNotNull('expo_push_token')
            ->where('is_active', true)
            ->get();

        if ($users->isEmpty()) return;

        $uid      = $data['uid'] ?? 'Unknown';
        $userName = $data['user_name'] ?? null;
        $location = $data['gate_mac'] ?? 'Entrance Gate';

        [$title, $body] = match ($alertType) {
            'invalid'   => [
                'Unregistered RFID Detected',
                "Unregistered card {$uid} at {$location}",
            ],
            'expired'   => [
                'Expired RFID Detected',
                "Expired card {$uid} at {$location}" . ($userName ? " - {$userName}" : ''),
            ],
            'suspended' => [
                'Suspended RFID Detected',
                "Suspended card {$uid} at {$location}" . ($userName ? " - {$userName}" : ''),
            ],
            'lost'      => [
                'Lost RFID Detected',
                "Lost card {$uid} at {$location}" . ($userName ? " - {$userName}" : ''),
            ],
            default     => [
                'Unknown Card Detected',
                "Unknown card {$uid} scanned at {$location}",
            ],
        };

        $messages = $users->map(fn($user) => [
            'to'        => $user->expo_push_token,
            'sound'     => 'default',
            'title'     => $title,
            'body'      => $body,
            'data'      => [
                'type'          => 'rfid_alert',
                'alert_type'    => $alertType,
                'uid'           => $uid,
                'user_name'     => $userName,
                'vehicle_plate' => $data['vehicle_plate'] ?? 'N/A',
                'gate_mac'      => $data['gate_mac'] ?? null,
                'timestamp'     => now()->toISOString(),
            ],
            'channelId'         => 'rfid-alerts',
            'priority'          => 'high',
            'color'             => '#B22020',
            'vibrate'           => [0, 500, 200, 500],
        ])->values()->all();

        self::send($messages);
        Log::info('RFID alert sent', ['type' => $alertType, 'uid' => $uid, 'recipients' => $users->count()]);
    }

    // ── 5. Guest Request ──────────────────────────────────────────────────────
    // Triggered when a new guest pass is created.
    // Sent to security/admin roles.
    public static function sendGuestRequest(string $guestName, string $plate, string $purpose): void
    {
        $users = SysUser::whereIn('role', ['security', 'admin'])
            ->whereNotNull('expo_push_token')
            ->where('is_active', true)
            ->get();

        if ($users->isEmpty()) return;

        $messages = $users->map(fn($user) => [
            'to'        => $user->expo_push_token,
            'sound'     => 'default',
            'title'     => 'New Guest Access Request',
            'body'      => "{$guestName} ({$plate}) - {$purpose}",
            'data'      => [
                'type'    => 'guest_request',
                'name'    => $guestName,
                'plate'   => $plate,
                'purpose' => $purpose,
                'timestamp' => now()->toISOString(),
            ],
            'channelId' => 'guest-requests',
            'priority'  => 'high',
            'color'     => '#fd7e14',
        ])->values()->all();

        self::send($messages);
        Log::info('Guest request notification sent', ['name' => $guestName, 'plate' => $plate]);
    }

    // ── 6. Spot Malfunction ───────────────────────────────────────────────────
    // Triggered when a parking spot sensor reports a malfunction.
    // Sent to admin/ssd roles.
    public static function sendMalfunctionAlert(string $spaceCode, string $reportedBy, string $reporterRole, ?string $reason): void
    {
        $users = SysUser::whereIn('role', ['admin', 'ssd'])
            ->whereNotNull('expo_push_token')
            ->where('is_active', true)
            ->get();

        if ($users->isEmpty()) return;

        $roleLabel = match ($reporterRole) {
            'security' => 'Security Guard',
            'admin'    => 'Admin',
            'ssd'      => 'SSD',
            default    => ucfirst($reporterRole),
        };

        $spaceObj = \App\Models\ParkingSpace::where('space_code', $spaceCode)->first();
        $floorLevel = $spaceObj?->floor_level ?? 'Unknown Floor';

        $body = "{$floorLevel} • Reported by {$roleLabel} {$reportedBy}" . ($reason ? " • {$reason}" : '');

        $messages = $users->map(fn($user) => [
            'to'        => $user->expo_push_token,
            'sound'     => 'default',
            'title'     => "⚠️ Spot {$spaceCode} Malfunctioned",
            'body'      => $body,
            'data'      => [
                'type'        => 'spot_malfunction',
                'space_code'  => $spaceCode,
                'floor_level' => $floorLevel,
                'reported_by' => $reportedBy,
                'reason'      => $reason,
                'timestamp'   => now()->toISOString(),
            ],
            'channelId' => 'malfunctions',
            'priority'  => 'high',
        ])->values()->all();

        self::send($messages);
        Log::info('Malfunction alert sent', ['space_code' => $spaceCode, 'recipients' => $users->count()]);
    }

    // ── Internal batch sender ─────────────────────────────────────────────────
    private static function send(array $messages): void
    {
        foreach (array_chunk($messages, 100) as $chunk) {
            try {
                $response = Http::post(self::EXPO_PUSH_URL, $chunk);
                if ($response->failed()) {
                    Log::error('Expo push failed', ['status' => $response->status(), 'body' => $response->body()]);
                }
            } catch (\Exception $e) {
                Log::error('Expo push exception', ['message' => $e->getMessage()]);
            }
        }
    }
}
