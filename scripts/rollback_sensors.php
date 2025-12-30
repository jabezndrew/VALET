<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SensorAssignment;

echo "Rolling back to original sensor configuration...\n\n";

// Get all unique MAC addresses
$macAddresses = SensorAssignment::select('mac_address')
    ->distinct()
    ->pluck('mac_address')
    ->toArray();

echo "Found " . count($macAddresses) . " unique ESP32 devices\n\n";

$totalDeleted = 0;

foreach ($macAddresses as $mac) {
    echo "Processing MAC: {$mac}\n";

    // Delete sensors 2-5, keep only sensor 1
    $deleted = SensorAssignment::where('mac_address', $mac)
        ->whereIn('sensor_index', [2, 3, 4, 5])
        ->delete();

    echo "  Deleted {$deleted} extra sensors (kept sensor 1 only)\n\n";
    $totalDeleted += $deleted;
}

echo "========================================\n";
echo "Summary:\n";
echo "  ESP32 Devices: " . count($macAddresses) . "\n";
echo "  Sensors Deleted: {$totalDeleted}\n";
echo "  Sensors Remaining: " . SensorAssignment::count() . "\n";
echo "========================================\n";
