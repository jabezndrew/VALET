<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ParkingFloor;
use App\Models\ParkingColumn;
use App\Models\ParkingConfig;

class ParkingConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        ParkingConfig::set('max_slots_per_column', '20', 'Maximum number of parking slots per column');

        $floors = [
            ['floor_number' => 1, 'floor_name' => '1st Floor', 'display_order' => 1],
            ['floor_number' => 2, 'floor_name' => '2nd Floor', 'display_order' => 2],
            ['floor_number' => 3, 'floor_name' => '3rd Floor', 'display_order' => 3],
            ['floor_number' => 4, 'floor_name' => '4th Floor', 'display_order' => 4],
        ];

        $columns = ['A', 'B', 'C', 'D'];

        foreach ($floors as $floorData) {
            $floor = ParkingFloor::create($floorData);

            foreach ($columns as $index => $columnCode) {
                ParkingColumn::create([
                    'floor_id' => $floor->id,
                    'column_code' => $columnCode,
                    'column_name' => "Column {$columnCode}",
                    'display_order' => $index + 1,
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('Parking configuration seeded successfully!');
        $this->command->info('Created 4 floors with 4 columns each (A, B, C, D)');
        $this->command->info('Each column can have up to 20 slots');
    }
}
