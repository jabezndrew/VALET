<?php

/**
 * Helper script to manually add parking slot labels with positions
 *
 * Usage: php scripts/add_parking_slots.php
 *
 * This script helps you add parking spaces with custom labels and positions.
 * Edit the $slots array below to define your parking slots.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ParkingSpace;

// Define your parking slots here
// Format: [space_code, floor_number, column_code, slot_number, floor_level, x_position, y_position, sensor_id, slot_name]
$slots = [
    // Floor 4, Section A
    ['4A1', 4, 'A', 1, '4th Floor', 1035, 174, 407, '4A1'],

    // Floor 4, Section B (B4, B3, B2, B1)
    ['4B4', 4, 'B', 4, '4th Floor', 747, 52, 401, '4B4'],
    ['4B3', 4, 'B', 3, '4th Floor', 814, 52, 402, '4B3'],
    ['4B2', 4, 'B', 2, '4th Floor', 882, 52, 403, '4B2'],
    ['4B1', 4, 'B', 1, '4th Floor', 950, 52, 404, '4B1'],

    // Floor 4, Section C (C1, C2)
    ['4C1', 4, 'C', 1, '4th Floor', 660, 155, 405, '4C1'],
    ['4C2', 4, 'C', 2, '4th Floor', 660, 225, 406, '4C2'],

    // Floor 4, Section D (D7 to D1)
    ['4D7', 4, 'D', 7, '4th Floor', 155, 304, 414, '4D7'],
    ['4D6', 4, 'D', 6, '4th Floor', 230, 304, 413, '4D6'],
    ['4D5', 4, 'D', 5, '4th Floor', 312, 304, 412, '4D5'],
    ['4D4', 4, 'D', 4, '4th Floor', 380, 304, 411, '4D4'],
    ['4D3', 4, 'D', 3, '4th Floor', 462, 304, 410, '4D3'],
    ['4D2', 4, 'D', 2, '4th Floor', 530, 304, 409, '4D2'],
    ['4D1', 4, 'D', 1, '4th Floor', 597, 304, 408, '4D1'],

    // Floor 4, Section E (E3, E2, E1)
    ['4E3', 4, 'E', 3, '4th Floor', 84, 476, 417, '4E3'],
    ['4E2', 4, 'E', 2, '4th Floor', 84, 567, 416, '4E2'],
    ['4E1', 4, 'E', 1, '4th Floor', 84, 665, 415, '4E1'],

    // Floor 4, Section F (F1 to F7)
    ['4F1', 4, 'F', 1, '4th Floor', 177, 784, 418, '4F1'],
    ['4F2', 4, 'F', 2, '4th Floor', 245, 784, 419, '4F2'],
    ['4F3', 4, 'F', 3, '4th Floor', 327, 784, 420, '4F3'],
    ['4F4', 4, 'F', 4, '4th Floor', 395, 784, 421, '4F4'],
    ['4F5', 4, 'F', 5, '4th Floor', 462, 784, 422, '4F5'],
    ['4F6', 4, 'F', 6, '4th Floor', 545, 784, 423, '4F6'],
    ['4F7', 4, 'F', 7, '4th Floor', 612, 784, 424, '4F7'],

    // Floor 4, Section G (G1 to G5)
    ['4G1', 4, 'G', 1, '4th Floor', 752, 889, 425, '4G1'],
    ['4G2', 4, 'G', 2, '4th Floor', 752, 972, 426, '4G2'],
    ['4G3', 4, 'G', 3, '4th Floor', 752, 1062, 427, '4G3'],
    ['4G4', 4, 'G', 4, '4th Floor', 752, 1152, 428, '4G4'],
    ['4G5', 4, 'G', 5, '4th Floor', 752, 1242, 429, '4G5'],

    // Floor 4, Section H (H1 to H3)
    ['4H1', 4, 'H', 1, '4th Floor', 822, 1342, 430, '4H1'],
    ['4H2', 4, 'H', 2, '4th Floor', 905, 1342, 431, '4H2'],
    ['4H3', 4, 'H', 3, '4th Floor', 972, 1342, 432, '4H3'],

    // Floor 4, Section I (I5 to I1)
    ['4I5', 4, 'I', 5, '4th Floor', 1022, 889, 437, '4I5'],
    ['4I4', 4, 'I', 4, '4th Floor', 1022, 972, 436, '4I4'],
    ['4I3', 4, 'I', 3, '4th Floor', 1022, 1062, 435, '4I3'],
    ['4I2', 4, 'I', 2, '4th Floor', 1022, 1152, 434, '4I2'],
    ['4I1', 4, 'I', 1, '4th Floor', 1022, 1242, 433, '4I1'],

    // Floor 4, Section J (J5 to J1)
    ['4J5', 4, 'J', 5, '4th Floor', 410, 559, 442, '4J5'],
    ['4J4', 4, 'J', 4, '4th Floor', 470, 559, 441, '4J4'],
    ['4J3', 4, 'J', 3, '4th Floor', 560, 559, 440, '4J3'],
    ['4J2', 4, 'J', 2, '4th Floor', 650, 559, 439, '4J2'],
    ['4J1', 4, 'J', 1, '4th Floor', 732, 559, 438, '4J1'],
];

echo "Starting to add parking slots...\n\n";

$created = 0;
$updated = 0;
$errors = 0;

foreach ($slots as $slot) {
    [$spaceCode, $floorNumber, $columnCode, $slotNumber, $floorLevel, $x, $y, $sensorId, $slotName] = $slot;

    try {
        // Check if slot already exists
        $existing = ParkingSpace::where('space_code', $spaceCode)->first();

        if ($existing) {
            // Update existing slot
            $existing->update([
                'floor_number' => $floorNumber,
                'column_code' => $columnCode,
                'slot_number' => $slotNumber,
                'floor_level' => $floorLevel,
                'x_position' => $x,
                'y_position' => $y,
                'sensor_id' => $sensorId,
                'slot_name' => $slotName,
            ]);
            $updated++;
            echo "[OK] Updated: {$slotName} at ({$x}, {$y})\n";
        } else {
            // Create new slot
            ParkingSpace::create([
                'space_code' => $spaceCode,
                'floor_number' => $floorNumber,
                'column_code' => $columnCode,
                'slot_number' => $slotNumber,
                'floor_level' => $floorLevel,
                'x_position' => $x,
                'y_position' => $y,
                'sensor_id' => $sensorId,
                'slot_name' => $slotName,
                'is_occupied' => false,
                'is_active' => true,
            ]);
            $created++;
            echo "[OK] Created: {$slotName} at ({$x}, {$y})\n";
        }
    } catch (\Exception $e) {
        $errors++;
        echo "[ERROR] Error with {$slotName}: " . $e->getMessage() . "\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Summary:\n";
echo "  Created: {$created} slots\n";
echo "  Updated: {$updated} slots\n";
echo "  Errors:  {$errors}\n";
echo str_repeat("=", 50) . "\n";
