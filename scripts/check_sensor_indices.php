<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SensorAssignment;

echo "Checking sensor indices in database...\n\n";

$sensors = SensorAssignment::select('id', 'mac_address', 'sensor_index', 'space_code', 'status')
    ->orderBy('mac_address')
    ->orderBy('sensor_index')
    ->get();

echo "Total sensors: " . $sensors->count() . "\n\n";

$groupedByMac = $sensors->groupBy('mac_address');

foreach ($groupedByMac as $mac => $macSensors) {
    echo "MAC Address: {$mac}\n";
    echo "  Sensors: " . $macSensors->count() . "\n";

    foreach ($macSensors as $sensor) {
        $space = $sensor->space_code ?? 'unassigned';
        echo "  - Index: {$sensor->sensor_index} | Space: {$space} | Status: {$sensor->status}\n";
    }
    echo "\n";
}
