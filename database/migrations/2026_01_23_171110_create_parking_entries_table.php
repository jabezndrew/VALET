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
        Schema::create('parking_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_type'); // 'rfid' or 'guest'
            $table->foreignId('rfid_tag_id')->nullable()->constrained('rfid_tags')->onDelete('cascade');
            $table->foreignId('guest_access_id')->nullable()->constrained('guest_access')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('sys_users')->onDelete('set null');
            $table->string('vehicle_plate')->nullable();
            $table->dateTime('entry_time');
            $table->dateTime('exit_time')->nullable();
            $table->integer('duration_minutes')->nullable(); // calculated on exit
            $table->enum('status', ['parked', 'exited'])->default('parked');
            $table->string('entry_gate_mac')->nullable(); // ESP32 MAC address
            $table->string('exit_gate_mac')->nullable();
            $table->timestamps();

            // Index for faster queries
            $table->index(['user_id', 'status']);
            $table->index(['entry_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parking_entries');
    }
};
