<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ParkingSpace;

class ParkingSpacesSeeder extends Seeder
{
    public function run(): void{
        $parkingSpaces = [
            [
                'sensor_id' => 201,
                'slot_name' => '2B1',
                'section' => 'B',
                'is_occupied' => false,
                'is_active' => true,
                'distance_cm' => null,
                'floor_level' => '2nd Floor',
                'x_position' => 100.00,
                'y_position' => 100.00,
                'rotation' => 0,
                'width' => 80.00,
                'height' => 120.00,
            ]
        ];

        foreach ($parkingSpaces as $space) {
            ParkingSpace::updateOrCreate(
                ['sensor_id' => $space['sensor_id']],
                $space
            );
        }
    }
}
