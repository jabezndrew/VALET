<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ParkingSpace;

$spaces = ParkingSpace::select('id', 'space_code', 'slot_name', 'floor_level', 'sensor_id', 'x_position', 'y_position')
    ->orderBy('floor_level')
    ->orderBy('space_code')
    ->get();

echo "Total Parking Spaces: " . $spaces->count() . "\n\n";
echo str_repeat("=", 90) . "\n";
printf("%-5s | %-10s | %-15s | %-10s | %-8s | %-15s\n",
    "ID", "Code", "Label", "Floor", "Sensor", "Position (X, Y)");
echo str_repeat("=", 90) . "\n";

foreach ($spaces as $s) {
    printf("%-5s | %-10s | %-15s | %-10s | %-8s | (%4s, %4s)\n",
        $s->id,
        $s->space_code ?? 'N/A',
        $s->slot_name ?? 'N/A',
        $s->floor_level ?? 'N/A',
        $s->sensor_id ?? 'N/A',
        $s->x_position ?? 'N/A',
        $s->y_position ?? 'N/A'
    );
}

echo str_repeat("=", 90) . "\n";
