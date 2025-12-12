<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parking_spaces', function (Blueprint $table) {
            $table->string('space_code', 10)->nullable()->after('sensor_id')->unique();
            $table->integer('floor_number')->nullable()->after('space_code');
            $table->string('column_code', 5)->nullable()->after('floor_number');
            $table->integer('slot_number')->nullable()->after('column_code');

            $table->index('space_code');
            $table->index(['floor_number', 'column_code', 'slot_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parking_spaces', function (Blueprint $table) {
            $table->dropIndex(['parking_spaces_space_code_unique']);
            $table->dropIndex(['parking_spaces_floor_number_column_code_slot_number_index']);
            $table->dropColumn(['space_code', 'floor_number', 'column_code', 'slot_number']);
        });
    }
};
