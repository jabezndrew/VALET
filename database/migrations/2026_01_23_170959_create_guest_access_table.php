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
        Schema::create('guest_access', function (Blueprint $table) {
            $table->id();
            $table->string('guest_id')->unique(); // e.g., GUEST-2026-001
            $table->string('name');
            $table->string('vehicle_plate');
            $table->string('phone')->nullable();
            $table->string('purpose')->nullable(); // reason for visit
            $table->dateTime('valid_from');
            $table->dateTime('valid_until'); // +24 hours from creation
            $table->enum('status', ['active', 'expired', 'used', 'cancelled'])->default('active');
            $table->foreignId('created_by')->constrained('sys_users')->onDelete('cascade'); // admin who created it
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_access');
    }
};
