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
        Schema::table('sensor_assignments', function (Blueprint $table) {
            // Drop the old unique constraint on mac_address
            $table->dropUnique(['mac_address']);

            // Add sensor_index field (1-5 for the 5 sensors on one ESP32)
            $table->tinyInteger('sensor_index')->default(1)->after('mac_address');

            // Add identify mode fields for blue LED feature
            $table->boolean('identify_mode')->default(false)->after('status');
            $table->timestamp('identify_started_at')->nullable()->after('identify_mode');

            // Create composite unique constraint (mac_address + sensor_index)
            $table->unique(['mac_address', 'sensor_index'], 'unique_sensor_identifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sensor_assignments', function (Blueprint $table) {
            // Drop composite unique constraint
            $table->dropUnique('unique_sensor_identifier');

            // Drop new columns
            $table->dropColumn(['sensor_index', 'identify_mode', 'identify_started_at']);

            // Restore old unique constraint
            $table->unique('mac_address');
        });
    }
};
