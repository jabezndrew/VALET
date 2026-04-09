<?php

namespace App\Services;

use App\Models\SysUser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushNotificationService
{
    private const EXPO_PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    // Send RFID alert to all security/SSD users
    public static function sendRfidAlert(string $alertType, array $data): void
    {
        // Get all security and SSD users with push tokens
        $users = SysUser::whereIn('role', ['security', 'ssd', 'admin'])
            ->whereNotNull('expo_push_token')
            ->where('is_active', true)
            ->get();

        if ($users->isEmpty()) {
            Log::info('No users with push tokens found for RFID alert');
            return;
        }

        $title = self::getAlertTitle($alertType);
        $body = self::getAlertBody($alertType, $data);

        $messages = [];
        foreach ($users as $user) {
            $messages[] = [
                'to' => $user->expo_push_token,
                'sound' => 'default',
                'title' => $title,
                'body' => $body,
                'data' => [
                    'alert_type' => $alertType,
                    'uid' => $data['uid'] ?? null,
                    'user_name' => $data['user_name'] ?? 'Unknown',
                    'vehicle_plate' => $data['vehicle_plate'] ?? 'N/A',
                    'timestamp' => now()->toISOString(),
                    'gate_mac' => $data['gate_mac'] ?? null,
                ],
                'channelId' => 'rfid-alerts',
                'priority' => 'high',
            ];
        }

        // Send in batches of 100 (Expo limit)
        $chunks = array_chunk($messages, 100);

        foreach ($chunks as $chunk) {
            try {
                $response = Http::post(self::EXPO_PUSH_URL, $chunk);

                if ($response->failed()) {
                    Log::error('Expo push notification failed', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Expo push notification exception', [
                    'message' => $e->getMessage()
                ]);
            }
        }

        Log::info('RFID alert sent', [
            'alert_type' => $alertType,
            'recipients' => $users->count()
        ]);
    }

    // Send malfunction report alert to admin/ssd users
    public static function sendMalfunctionAlert(string $spaceCode, string $reportedBy, string $reporterRole, ?string $reason): void
    {
        $users = SysUser::whereIn('role', ['admin', 'ssd'])
            ->whereNotNull('expo_push_token')
            ->where('is_active', true)
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        $roleLabel = match ($reporterRole) {
            'security' => 'Security Guard',
            'admin'    => 'Admin',
            'ssd'      => 'SSD',
            default    => ucfirst($reporterRole),
        };

        $body = "Spot {$spaceCode} flagged as malfunctioned by {$roleLabel} {$reportedBy}.";
        if ($reason) {
            $body .= " Reason: {$reason}";
        }

        $messages = $users->map(fn($user) => [
            'to'        => $user->expo_push_token,
            'sound'     => 'default',
            'title'     => '⚠️ Spot Malfunction Reported',
            'body'      => $body,
            'data'      => [
                'alert_type'  => 'spot_malfunction',
                'space_code'  => $spaceCode,
                'reported_by' => $reportedBy,
                'reason'      => $reason,
                'timestamp'   => now()->toISOString(),
            ],
            'channelId' => 'rfid-alerts',
            'priority'  => 'high',
        ])->values()->all();

        foreach (array_chunk($messages, 100) as $chunk) {
            try {
                Http::post(self::EXPO_PUSH_URL, $chunk);
            } catch (\Exception $e) {
                Log::error('Malfunction push notification failed', ['message' => $e->getMessage()]);
            }
        }

        Log::info('Malfunction alert sent', ['space_code' => $spaceCode, 'recipients' => $users->count()]);
    }

    // Get alert title based on type
    private static function getAlertTitle(string $alertType): string
    {
        return match ($alertType) {
            'invalid' => 'Unregistered RFID Detected',
            'expired' => 'Expired RFID Detected',
            'suspended' => 'Suspended RFID Detected',
            'lost' => 'Lost RFID Detected',
            'unknown' => 'Unknown Card Detected',
            default => 'RFID Alert'
        };
    }

    // Get alert body based on type and data
    private static function getAlertBody(string $alertType, array $data): string
    {
        $uid = $data['uid'] ?? 'Unknown';
        $userName = $data['user_name'] ?? 'Unknown';
        $vehiclePlate = $data['vehicle_plate'] ?? 'N/A';

        return match ($alertType) {
            'invalid' => "Unregistered RFID card ({$uid}) attempted access at entrance gate.",
            'expired' => "Expired RFID for {$userName} ({$vehiclePlate}) attempted access.",
            'suspended' => "Suspended RFID for {$userName} ({$vehiclePlate}) attempted access.",
            'lost' => "Lost RFID for {$userName} ({$vehiclePlate}) attempted access.",
            'unknown' => "Unknown card format detected at entrance gate.",
            default => "RFID alert: {$uid}"
        };
    }
}
