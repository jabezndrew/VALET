<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 2nd Floor fixes ──────────────────────────────────────────
        // id=193: was slot=2B1 code=2A1 at (100,100) → fix to 2A1 at correct position
        DB::table('parking_spaces')->where('id', 193)->update([
            'slot_name'  => '2A1', 'space_code' => '2A1',
            'x_position' => 1040.00, 'y_position' => 154.00, 'rotation' => 270,
        ]);
        // id=8: was slot=2B3 code=2B3 at (260,100) → fix position
        DB::table('parking_spaces')->where('id', 8)->update([
            'slot_name'  => '2B3', 'space_code' => '2B3',
            'x_position' => 838.00, 'y_position' => 48.00, 'rotation' => 0,
        ]);
        // id=7: was slot=2B2 code=2B4 at (180,100) → fix to 2B4 at correct position
        DB::table('parking_spaces')->where('id', 7)->update([
            'slot_name'  => '2B4', 'space_code' => '2B4',
            'x_position' => 770.00, 'y_position' => 48.00, 'rotation' => 0,
        ]);

        // ── 4th Floor fixes ──────────────────────────────────────────
        // Fix A1: was slot=4B4 code=4A1 at (750,48) → fix slot_name and position
        DB::table('parking_spaces')->where('floor_level', '4th Floor')
            ->where('space_code', '4A1')->update([
                'slot_name'  => '4A1',
                'x_position' => 1040.00, 'y_position' => 154.00, 'rotation' => 270,
            ]);
        // Fix B1: was slot=4B1 code=4B2 at (952.5,48) → correct slot/code/position
        DB::table('parking_spaces')->where('floor_level', '4th Floor')
            ->where('id', 187)->update([
                'slot_name' => '4B1', 'space_code' => '4B1',
                'x_position' => 970.00, 'y_position' => 48.00, 'rotation' => 0,
            ]);
        // Fix B2: was slot=4B2 code=4B3 at (885,48) → correct slot/code/position
        DB::table('parking_spaces')->where('floor_level', '4th Floor')
            ->where('id', 186)->update([
                'slot_name' => '4B2', 'space_code' => '4B2',
                'x_position' => 903.00, 'y_position' => 48.00, 'rotation' => 0,
            ]);
        // Fix B3: was slot=4B3 code=4B4 at (817.5,48) → correct slot/code/position
        DB::table('parking_spaces')->where('floor_level', '4th Floor')
            ->where('id', 185)->update([
                'slot_name' => '4B3', 'space_code' => '4B3',
                'x_position' => 838.00, 'y_position' => 48.00, 'rotation' => 0,
            ]);

        // Add 4B4 if missing
        if (!DB::table('parking_spaces')->where('floor_level', '4th Floor')->where('space_code', '4B4')->exists()) {
            DB::table('parking_spaces')->insert([
                'slot_name'   => '4B4', 'space_code' => '4B4',
                'floor_level' => '4th Floor',
                'x_position'  => 770.00, 'y_position' => 48.00, 'rotation' => 0,
                'sensor_id'   => null, 'is_occupied' => 0, 'is_active' => 1,
                'created_at'  => now(), 'updated_at' => now(),
            ]);
        }

        // Add 4C1 if missing
        if (!DB::table('parking_spaces')->where('floor_level', '4th Floor')->where('space_code', '4C1')->exists()) {
            DB::table('parking_spaces')->insert([
                'slot_name'   => '4C1', 'space_code' => '4C1',
                'floor_level' => '4th Floor',
                'x_position'  => 700.00, 'y_position' => 130.00, 'rotation' => 270,
                'sensor_id'   => null, 'is_occupied' => 0, 'is_active' => 1,
                'created_at'  => now(), 'updated_at' => now(),
            ]);
        }

        // Remove the misplaced 4C1 duplicate (slot=4C1 code=4B1 at 675,142.5) if still present
        DB::table('parking_spaces')
            ->where('floor_level', '4th Floor')
            ->where('slot_name', '4C1')
            ->where('space_code', '4B1')
            ->delete();
    }

    public function down(): void
    {
        // 2nd Floor rollback
        DB::table('parking_spaces')->where('id', 193)->update([
            'slot_name' => '2B1', 'space_code' => '2A1',
            'x_position' => 100.00, 'y_position' => 100.00, 'rotation' => 0,
        ]);
        DB::table('parking_spaces')->where('id', 8)->update([
            'slot_name' => '2B3', 'space_code' => '2B3',
            'x_position' => 260.00, 'y_position' => 100.00, 'rotation' => 0,
        ]);
        DB::table('parking_spaces')->where('id', 7)->update([
            'slot_name' => '2B2', 'space_code' => '2B4',
            'x_position' => 180.00, 'y_position' => 100.00, 'rotation' => 0,
        ]);

        // 4th Floor rollback
        DB::table('parking_spaces')->where('floor_level', '4th Floor')
            ->where('space_code', '4A1')->update([
                'slot_name' => '4B4',
                'x_position' => 750.00, 'y_position' => 48.00, 'rotation' => 0,
            ]);
        DB::table('parking_spaces')->where('id', 187)->update([
            'slot_name' => '4B1', 'space_code' => '4B2',
            'x_position' => 952.50, 'y_position' => 48.00,
        ]);
        DB::table('parking_spaces')->where('id', 186)->update([
            'slot_name' => '4B2', 'space_code' => '4B3',
            'x_position' => 885.00, 'y_position' => 48.00,
        ]);
        DB::table('parking_spaces')->where('id', 185)->update([
            'slot_name' => '4B3', 'space_code' => '4B4',
            'x_position' => 817.50, 'y_position' => 48.00,
        ]);
        DB::table('parking_spaces')->where('floor_level', '4th Floor')
            ->where('space_code', '4B4')->whereNull('sensor_id')->delete();
        DB::table('parking_spaces')->where('floor_level', '4th Floor')
            ->where('space_code', '4C1')->whereNull('sensor_id')->delete();
    }
};
