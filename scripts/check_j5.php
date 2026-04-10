<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ParkingSpace;

echo "Checking J section slots for 4th Floor:\n\n";

$jSlots = ParkingSpace::where('floor_level', '4th Floor')
    ->where('column_code', 'J')
    ->orderBy('slot_number')
    ->get(['slot_name', 'space_code', 'x_position', 'y_position', 'is_active']);

foreach($jSlots as $slot) {
    echo $slot->slot_name . " - x:{$slot->x_position} y:{$slot->y_position} active:" . ($slot->is_active ? 'YES' : 'NO') . "\n";
}

echo "\nTotal J slots found: " . $jSlots->count() . "\n";

// Check specifically for 4J5
$j5 = ParkingSpace::where('slot_name', '4J5')->first();
if($j5) {
    echo "\n4J5 EXISTS in database!\n";
    echo "Position: x={$j5->x_position}, y={$j5->y_position}\n";
    echo "Active: " . ($j5->is_active ? 'YES' : 'NO') . "\n";
} else {
    echo "\n4J5 DOES NOT EXIST in database!\n";
}
