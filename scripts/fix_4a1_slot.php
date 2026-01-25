<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ParkingSpace;

// Find the 4A1 slot by space_code
$slot = ParkingSpace::where('space_code', '4A1')->first();

if ($slot) {
    echo "Found slot 4A1 (ID: {$slot->id})\n";
    echo "Updating position and label...\n";

    $slot->update([
        'slot_name' => '4A1',
        'x_position' => 1035,
        'y_position' => 174,
        'sensor_id' => 407,
        'floor_level' => '4th Floor',
        'floor_number' => 4,
        'column_code' => 'A',
        'slot_number' => 1,
        'is_active' => true,
    ]);

    echo "✓ Updated 4A1 slot successfully!\n";
    echo "  - Position: (1035, 174)\n";
    echo "  - Label: 4A1\n";
    echo "  - Sensor ID: 407\n";
} else {
    echo "✗ Slot 4A1 not found!\n";
}
