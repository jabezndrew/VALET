<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfid_scan_logs', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->nullable();
            $table->enum('status', ['valid', 'invalid', 'expired', 'suspended', 'lost', 'unknown']);
            $table->string('message');
            $table->enum('scan_type', ['entry', 'exit'])->default('entry');
            $table->string('gate_mac')->nullable();
            $table->string('user_name')->nullable();
            $table->string('vehicle_plate')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfid_scan_logs');
    }
};
