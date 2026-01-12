<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SensorAssignment;

echo "Registering all 5 sensors for each ESP32...\n\n";

// Get all unique MAC addresses
$macAddresses = SensorAssignment::select('mac_address')
    ->distinct()
    ->pluck('mac_address')
    ->toArray();

echo "Found " . count($macAddresses) . " unique ESP32 devices\n\n";

$totalCreated = 0;
$totalExisting = 0;

foreach ($macAddresses as $mac) {
    echo "Processing MAC: {$mac}\n";

    // Get existing sensors for this MAC
    $existingSensors = SensorAssignment::where('mac_address', $mac)
        ->pluck('sensor_index')
        ->toArray();

    echo "  Existing sensors: " . implode(', ', $existingSensors) . "\n";

    // Register all 5 sensors (1-5)
    for ($sensorIndex = 1; $sensorIndex <= 5; $sensorIndex++) {

        if (in_array($sensorIndex, $existingSensors)) {
            echo "  - Sensor {$sensorIndex}: Already exists\n";
            $totalExisting++;
            continue;
        }

        // Create new sensor
        SensorAssignment::create([
            'mac_address' => $mac,
            'sensor_index' => $sensorIndex,
            'status' => 'unassigned',
            'last_seen' => now()
        ]);

        echo "  - Sensor {$sensorIndex}: Created\n";
        $totalCreated++;
    }

    echo "\n";
}

echo "========================================\n";
echo "Summary:\n";
echo "  ESP32 Devices: " . count($macAddresses) . "\n";
echo "  Sensors Created: {$totalCreated}\n";
echo "  Sensors Already Existed: {$totalExisting}\n";
echo "  Total Sensors: " . ($totalCreated + $totalExisting) . "\n";
echo "========================================\n";
