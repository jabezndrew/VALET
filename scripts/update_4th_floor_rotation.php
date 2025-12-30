<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ParkingSpace;

echo "Updating 4th Floor rotation...\n\n";

// Update Section E to 270 degrees
$updates = [
    ['space_code' => '4E3', 'rotation' => 270],
    ['space_code' => '4E2', 'rotation' => 270],
    ['space_code' => '4E1', 'rotation' => 270],
];

$updated = 0;
foreach ($updates as $update) {
    $space = ParkingSpace::where(function($query) use ($update) {
        $query->where('space_code', $update['space_code'])
              ->orWhere('slot_name', $update['space_code']);
    })->first();

    if ($space) {
        $space->update(['rotation' => $update['rotation']]);
        echo "✓ Updated {$update['space_code']} to {$update['rotation']}°\n";
        $updated++;
    } else {
        echo "✗ Slot {$update['space_code']} not found\n";
    }
}

echo "\n========================================\n";
echo "Total updated: {$updated} slots\n";
echo "========================================\n";
