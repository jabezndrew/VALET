<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ParkingSpotSeeder extends Seeder
{

    public function run(): void
    {

        if (DB::table('parking_spaces')->count() > 0) {
            return;
        }


        $parkingSpaces = [
            [
                'sensor_id' => 401,
                'is_occupied' => false,
                'distance_cm' => 250, 
                'floor_level' => '4th Floor',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'sensor_id' => 402,
                'is_occupied' => true,
                'distance_cm' => 15, 
                'floor_level' => '4th Floor',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'sensor_id' => 403,
                'is_occupied' => false,
                'distance_cm' => 280,
                'floor_level' => '4th Floor',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'sensor_id' => 404,
                'is_occupied' => true,
                'distance_cm' => 12,
                'floor_level' => '4th Floor',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'sensor_id' => 405,
                'is_occupied' => false,
                'distance_cm' => 320,
                'floor_level' => '4th Floor',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('parking_spaces')->insert($parkingSpaces);
    }
}