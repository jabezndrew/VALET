<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ParkingSpace;

// Find and delete B2 and B3 slots (sensor_id 2 and 3)
$slots = ParkingSpace::whereIn('sensor_id', [2, 3])->get();

echo "Found slots to delete:\n";
foreach ($slots as $slot) {
    $name = $slot->slot_name ?? 'Sensor ' . $slot->sensor_id;
    echo "- {$name} (ID: {$slot->id}, Sensor: {$slot->sensor_id})\n";
}

$deleted = ParkingSpace::whereIn('sensor_id', [2, 3])->delete();

echo "\nDeleted {$deleted} parking slot(s) (B2 and B3)\n";
