<?php

namespace App\Services;

use App\Models\SysUser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushNotificationService
{
    private const EXPO_PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    /**
     * Send RFID alert to all security/SSD users
     */
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

    /**
     * Get alert title based on type
     */
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

    /**
     * Get alert body based on type and data
     */
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
