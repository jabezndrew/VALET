<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ParkingSpace;

class ParkingSpacesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed initial parking spaces with real sensor data
        $parkingSpaces = [
            // 2nd Floor parking spaces (201-2xx)
            [
                'sensor_id' => 201,
                'slot_name' => '2B1',
                'section' => 'B',
                'is_occupied' => false,
                'is_active' => true,
                'distance_cm' => null,
                'floor_level' => '2nd Floor',
                'x_position' => 100.00, // TODO: Adjust based on your floor plan
                'y_position' => 100.00, // TODO: Adjust based on your floor plan
                'rotation' => 0,
                'width' => 80.00,
                'height' => 120.00,
            ],
            [
                'sensor_id' => 202,
                'slot_name' => '2B2',
                'section' => 'B',
                'is_occupied' => false,
                'is_active' => true,
                'distance_cm' => null,
                'floor_level' => '2nd Floor',
                'x_position' => 180.00, // TODO: Adjust based on your floor plan
                'y_position' => 100.00, // TODO: Adjust based on your floor plan
                'rotation' => 0,
                'width' => 80.00,
                'height' => 120.00,
            ],
            [
                'sensor_id' => 203,
                'slot_name' => '2B3',
                'section' => 'B',
                'is_occupied' => false,
                'is_active' => true,
                'distance_cm' => null,
                'floor_level' => '2nd Floor',
                'x_position' => 260.00, // TODO: Adjust based on your floor plan
                'y_position' => 100.00, // TODO: Adjust based on your floor plan
                'rotation' => 0,
                'width' => 80.00,
                'height' => 120.00,
            ],

            // 4th Floor parking spaces (401-405)
            [
                'sensor_id' => 401,
                'slot_name' => '4B4',
                'section' => 'B',
                'is_occupied' => false,
                'is_active' => true,
                'distance_cm' => null,
                'floor_level' => '4th Floor',
                'x_position' => 750.00,
                'y_position' => 48.00,
                'rotation' => 0,
                'width' => 80.00,
                'height' => 120.00,
            ],
            [
                'sensor_id' => 402,
                'slot_name' => '4B3',
                'section' => 'B',
                'is_occupied' => false,
                'is_active' => true,
                'distance_cm' => null,
                'floor_level' => '4th Floor',
                'x_position' => 817.50,
                'y_position' => 48.00,
                'rotation' => 0,
                'width' => 80.00,
                'height' => 120.00,
            ],
            [
                'sensor_id' => 403,
                'slot_name' => '4B2',
                'section' => 'B',
                'is_occupied' => false,
                'is_active' => true,
                'distance_cm' => null,
                'floor_level' => '4th Floor',
                'x_position' => 885.00,
                'y_position' => 48.00,
                'rotation' => 0,
                'width' => 80.00,
                'height' => 120.00,
            ],
            [
                'sensor_id' => 404,
                'slot_name' => '4B1',
                'section' => 'B',
                'is_occupied' => false,
                'is_active' => true,
                'distance_cm' => null,
                'floor_level' => '4th Floor',
                'x_position' => 952.50,
                'y_position' => 48.00,
                'rotation' => 0,
                'width' => 80.00,
                'height' => 120.00,
            ],
            [
                'sensor_id' => 405,
                'slot_name' => '4C1',
                'section' => 'C',
                'is_occupied' => false,
                'is_active' => true,
                'distance_cm' => null,
                'floor_level' => '4th Floor',
                'x_position' => 675.00,
                'y_position' => 142.50,
                'rotation' => 90,
                'width' => 80.00,
                'height' => 120.00,
            ],
        ];

        foreach ($parkingSpaces as $space) {
            ParkingSpace::updateOrCreate(
                ['sensor_id' => $space['sensor_id']],
                $space
            );
        }
    }
}
