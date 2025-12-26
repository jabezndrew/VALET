<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('mac_address', 17)->unique(); // e.g., "AA:BB:CC:DD:EE:FF"
            $table->string('space_code', 10)->nullable(); // e.g., "4A1" - nullable when unassigned
            $table->string('device_name', 100)->nullable(); // Optional friendly name like "Sensor 1"
            $table->enum('status', ['active', 'inactive', 'unassigned'])->default('unassigned');
            $table->timestamp('last_seen')->nullable(); // Last time sensor connected
            $table->string('firmware_version', 20)->nullable(); // Track firmware version
            $table->text('notes')->nullable(); // Admin notes about this sensor
            $table->timestamps();

            // Foreign key to parking_spaces (optional, for validation)
            $table->foreign('space_code')->references('space_code')->on('parking_spaces')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_assignments');
    }
};
