<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ParkingSpace;

class SeedParkingSpaces extends Command
{
    protected $signature = 'parking:seed';
    protected $description = 'Seed all parking spaces for all floors';

    public function handle()
    {
        $this->info('Adding parking slots for all floors...');

        // First, get all valid space codes we'll be creating
        $validSpaceCodes = [];
        $slotPositionsTemp = [
            'A1', 'B4', 'B3', 'B2', 'B1', 'C1', 'C2',
            'D7', 'D6', 'D5', 'D4', 'D3', 'D2', 'D1',
            'E3', 'E2', 'E1',
            'F1', 'F2', 'F3', 'F4', 'F5', 'F6', 'F7',
            'G1', 'G2', 'G3', 'G4', 'G5',
            'H1', 'H2', 'H3',
            'I5', 'I4', 'I3', 'I2', 'I1',
            'J5', 'J4', 'J3', 'J2', 'J1',
        ];

        foreach ([1, 2, 3, 4] as $floorNum) {
            foreach ($slotPositionsTemp as $slot) {
                $validSpaceCodes[] = $floorNum . $slot;
            }
        }

        // Delete any parking spaces that are NOT in our valid list (including NULL space_codes)
        $deleted = ParkingSpace::where(function($query) use ($validSpaceCodes) {
            $query->whereNotIn('space_code', $validSpaceCodes)
                  ->orWhereNull('space_code');
        })->delete();
        if ($deleted > 0) {
            $this->info("Deleted {$deleted} orphaned/invalid parking spaces");
        }

        // Define all 42 parking slot positions (same layout for all floors)
        $slotPositions = [
            // Section A (1 slot)
            ['A1', 'A', 1, 1040, 154, 270],

            // Section B (4 slots)
            ['B4', 'B', 4, 770, 48, 0],
            ['B3', 'B', 3, 838, 48, 0],
            ['B2', 'B', 2, 903, 48, 0],
            ['B1', 'B', 1, 970, 48, 0],

            // Section C (2 slots)
            ['C1', 'C', 1, 700, 130, 270],
            ['C2', 'C', 2, 700, 205, 270],

            // Section D (7 slots)
            ['D7', 'D', 7, 188, 304, 0],
            ['D6', 'D', 6, 256, 304, 0],
            ['D5', 'D', 5, 325, 304, 0],
            ['D4', 'D', 4, 393, 304, 0],
            ['D3', 'D', 3, 462, 304, 0],
            ['D2', 'D', 2, 530, 304, 0],
            ['D1', 'D', 1, 597, 304, 0],

            // Section E (3 slots)
            ['E3', 'E', 3, 84, 491, 270],
            ['E2', 'E', 2, 84, 567, 270],
            ['E1', 'E', 1, 84, 645, 270],

            // Section F (7 slots)
            ['F1', 'F', 1, 177, 784, 0],
            ['F2', 'F', 2, 245, 784, 0],
            ['F3', 'F', 3, 327, 784, 0],
            ['F4', 'F', 4, 395, 784, 0],
            ['F5', 'F', 5, 462, 784, 0],
            ['F6', 'F', 6, 545, 784, 0],
            ['F7', 'F', 7, 612, 784, 0],

            // Section G (5 slots)
            ['G1', 'G', 1, 752, 889, 270],
            ['G2', 'G', 2, 752, 972, 270],
            ['G3', 'G', 3, 752, 1062, 270],
            ['G4', 'G', 4, 752, 1152, 270],
            ['G5', 'G', 5, 752, 1242, 270],

            // Section H (3 slots)
            ['H1', 'H', 1, 822, 1342, 0],
            ['H2', 'H', 2, 895, 1342, 0],
            ['H3', 'H', 3, 972, 1342, 0],

            // Section I (5 slots)
            ['I5', 'I', 5, 1050, 889, 270],
            ['I4', 'I', 4, 1050, 972, 270],
            ['I3', 'I', 3, 1050, 1062, 270],
            ['I2', 'I', 2, 1050, 1152, 270],
            ['I1', 'I', 1, 1050, 1242, 270],

            // Section J (5 slots)
            ['J5', 'J', 5, 370, 559, 270],
            ['J4', 'J', 4, 460, 559, 270],
            ['J3', 'J', 3, 550, 559, 270],
            ['J2', 'J', 2, 640, 559, 270],
            ['J1', 'J', 1, 732, 559, 270],
        ];

        // Define floors to create
        $floors = [
            ['name' => '1st Floor', 'number' => 1, 'sensor_base' => 100],
            ['name' => '2nd Floor', 'number' => 2, 'sensor_base' => 200],
            ['name' => '3rd Floor', 'number' => 3, 'sensor_base' => 300],
            ['name' => '4th Floor', 'number' => 4, 'sensor_base' => 400],
        ];

        $totalCreated = 0;
        $totalUpdated = 0;

        foreach ($floors as $floor) {
            $this->info("Processing {$floor['name']}...");

            $slotNumber = 1;
            foreach ($slotPositions as $position) {
                list($sectionSlot, $column, $slotNum, $x, $y, $rotation) = $position;

                $slotName = $floor['number'] . $sectionSlot;
                $spaceCode = $floor['number'] . $sectionSlot;
                $sensorId = $floor['sensor_base'] + $slotNumber;

                // Delete orphaned entries
                ParkingSpace::where('space_code', $spaceCode)
                    ->whereNull('sensor_id')
                    ->delete();

                // Check if slot exists
                $existing = ParkingSpace::where('space_code', $spaceCode)->first();

                if (!$existing) {
                    $existing = ParkingSpace::where('floor_level', $floor['name'])
                        ->where(function($query) use ($sensorId, $slotName) {
                            $query->where('sensor_id', $sensorId)
                                  ->orWhere('slot_name', $slotName);
                        })
                        ->first();
                }

                if ($existing) {
                    $existing->update([
                        'slot_name' => $slotName,
                        'space_code' => $spaceCode,
                        'floor_number' => $floor['number'],
                        'column_code' => $column,
                        'slot_number' => $slotNum,
                        'floor_level' => $floor['name'],
                        'x_position' => $x,
                        'y_position' => $y,
                        'rotation' => $rotation,
                        'is_active' => true,
                    ]);
                    $totalUpdated++;
                } else {
                    ParkingSpace::create([
                        'slot_name' => $slotName,
                        'space_code' => $spaceCode,
                        'floor_number' => $floor['number'],
                        'column_code' => $column,
                        'slot_number' => $slotNum,
                        'sensor_id' => $sensorId,
                        'floor_level' => $floor['name'],
                        'x_position' => $x,
                        'y_position' => $y,
                        'rotation' => $rotation,
                        'is_occupied' => false,
                        'is_active' => true,
                    ]);
                    $totalCreated++;
                }

                $slotNumber++;
            }

            $this->info("  [OK] {$floor['name']} completed");
        }

        $this->newLine();
        $this->info('========================================');
        $this->info('Summary:');
        $this->info("  Created: {$totalCreated} slots");
        $this->info("  Updated: {$totalUpdated} slots");
        $this->info("  Total: " . ($totalCreated + $totalUpdated) . " slots");
        $this->info('========================================');

        return 0;
    }
}
