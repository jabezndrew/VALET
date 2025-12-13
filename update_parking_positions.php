<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ParkingSpace;

// Parking spot configurations based on React Native (scaled 1.5x for web)
// Original positions from mobile app, scaled by 1.5
$parkingConfig = [
    // Section A
    ['sensor_id' => 407, 'spot_id' => '4A1', 'x' => 1027.5, 'y' => 174, 'section' => 'A', 'rotation' => 180],
    // Section B (B4, B3, B2, B1)
    ['sensor_id' => 401, 'spot_id' => '4B4', 'x' => 750, 'y' => 48, 'section' => 'B'],
    ['sensor_id' => 402, 'spot_id' => '4B3', 'x' => 817.5, 'y' => 48, 'section' => 'B'],
    ['sensor_id' => 403, 'spot_id' => '4B2', 'x' => 885, 'y' => 48, 'section' => 'B'],
    ['sensor_id' => 404, 'spot_id' => '4B1', 'x' => 952.5, 'y' => 48, 'section' => 'B'],

    // Section C
    ['sensor_id' => 405, 'spot_id' => '4C1', 'x' => 675, 'y' => 142.5, 'section' => 'C', 'rotation' => 270],
    ['sensor_id' => 406, 'spot_id' => '4C2', 'x' => 675, 'y' => 210, 'section' => 'C', 'rotation' => 270],

    // Section D (D7 to D1)
    ['sensor_id' => 414, 'spot_id' => '4D7', 'x' => 150, 'y' => 300, 'section' => 'D'],
    ['sensor_id' => 413, 'spot_id' => '4D6', 'x' => 240, 'y' => 300, 'section' => 'D'],
    ['sensor_id' => 412, 'spot_id' => '4D5', 'x' => 315, 'y' => 300, 'section' => 'D'],
    ['sensor_id' => 411, 'spot_id' => '4D4', 'x' => 388.5, 'y' => 300, 'section' => 'D'],
    ['sensor_id' => 410, 'spot_id' => '4D3', 'x' => 465, 'y' => 300, 'section' => 'D'],
    ['sensor_id' => 409, 'spot_id' => '4D2', 'x' => 532.5, 'y' => 300, 'section' => 'D'],
    ['sensor_id' => 408, 'spot_id' => '4D1', 'x' => 600, 'y' => 300, 'section' => 'D'],

    // Section E
    ['sensor_id' => 417, 'spot_id' => '4E3', 'x' => 82.5, 'y' => 472.5, 'section' => 'E', 'rotation' => 180],
    ['sensor_id' => 416, 'spot_id' => '4E2', 'x' => 82.5, 'y' => 570, 'section' => 'E', 'rotation' => 180],
    ['sensor_id' => 415, 'spot_id' => '4E1', 'x' => 82.5, 'y' => 667.5, 'section' => 'E', 'rotation' => 180],

    // Section F (F1 to F7)
    ['sensor_id' => 418, 'spot_id' => '4F1', 'x' => 180, 'y' => 780, 'section' => 'F'],
    ['sensor_id' => 419, 'spot_id' => '4F2', 'x' => 247.5, 'y' => 780, 'section' => 'F'],
    ['sensor_id' => 420, 'spot_id' => '4F3', 'x' => 330, 'y' => 780, 'section' => 'F'],
    ['sensor_id' => 421, 'spot_id' => '4F4', 'x' => 397.5, 'y' => 780, 'section' => 'F'],
    ['sensor_id' => 422, 'spot_id' => '4F5', 'x' => 465, 'y' => 780, 'section' => 'F'],
    ['sensor_id' => 423, 'spot_id' => '4F6', 'x' => 547.5, 'y' => 780, 'section' => 'F'],
    ['sensor_id' => 424, 'spot_id' => '4F7', 'x' => 615, 'y' => 780, 'section' => 'F'],

    // Section G (G1 to G5)
    ['sensor_id' => 425, 'spot_id' => '4G1', 'x' => 750, 'y' => 885, 'section' => 'G'],
    ['sensor_id' => 426, 'spot_id' => '4G2', 'x' => 750, 'y' => 975, 'section' => 'G'],
    ['sensor_id' => 427, 'spot_id' => '4G3', 'x' => 750, 'y' => 1065, 'section' => 'G'],
    ['sensor_id' => 428, 'spot_id' => '4G4', 'x' => 750, 'y' => 1155, 'section' => 'G'],
    ['sensor_id' => 429, 'spot_id' => '4G5', 'x' => 750, 'y' => 1245, 'section' => 'G'],

    // Section H (H1 to H3)
    ['sensor_id' => 430, 'spot_id' => '4H1', 'x' => 840, 'y' => 1335, 'section' => 'H'],
    ['sensor_id' => 431, 'spot_id' => '4H2', 'x' => 907.5, 'y' => 1335, 'section' => 'H'],
    ['sensor_id' => 432, 'spot_id' => '4H3', 'x' => 975, 'y' => 1335, 'section' => 'H'],

    // Section I (I5 to I1)
    ['sensor_id' => 437, 'spot_id' => '4I5', 'x' => 1020, 'y' => 885, 'section' => 'I'],
    ['sensor_id' => 436, 'spot_id' => '4I4', 'x' => 1020, 'y' => 975, 'section' => 'I'],
    ['sensor_id' => 435, 'spot_id' => '4I3', 'x' => 1020, 'y' => 1065, 'section' => 'I'],
    ['sensor_id' => 434, 'spot_id' => '4I2', 'x' => 1020, 'y' => 1155, 'section' => 'I'],
    ['sensor_id' => 433, 'spot_id' => '4I1', 'x' => 1020, 'y' => 1245, 'section' => 'I'],

    // Section J (J5 to J1)
    ['sensor_id' => 442, 'spot_id' => '4J5', 'x' => 405, 'y' => 555, 'section' => 'J'],
    ['sensor_id' => 441, 'spot_id' => '4J4', 'x' => 480, 'y' => 555, 'section' => 'J'],
    ['sensor_id' => 440, 'spot_id' => '4J3', 'x' => 570, 'y' => 555, 'section' => 'J'],
    ['sensor_id' => 439, 'spot_id' => '4J2', 'x' => 660, 'y' => 555, 'section' => 'J'],
    ['sensor_id' => 438, 'spot_id' => '4J1', 'x' => 735, 'y' => 555, 'section' => 'J'],
];

echo "Updating parking space positions...\n\n";

foreach ($parkingConfig as $config) {
    // Find or create parking space
    $space = ParkingSpace::where('sensor_id', $config['sensor_id'])->first();

    if (!$space) {
        // Create new parking space
        $space = new ParkingSpace();
        $space->sensor_id = $config['sensor_id'];
        $space->floor_level = '4th Floor';
        $space->is_occupied = false;
        echo "Creating new space for sensor {$config['sensor_id']}\n";
    } else {
        echo "Updating space for sensor {$config['sensor_id']}\n";
    }

    $space->slot_name = $config['spot_id'];
    $space->x_position = $config['x'];
    $space->y_position = $config['y'];
    $space->section = $config['section'];
    $space->rotation = $config['rotation'] ?? 0;
    $space->is_active = true;
    $space->save();

    echo "  -> {$config['spot_id']} positioned at ({$config['x']}, {$config['y']})\n";
}

echo "\n✓ All parking spaces updated successfully!\n";
