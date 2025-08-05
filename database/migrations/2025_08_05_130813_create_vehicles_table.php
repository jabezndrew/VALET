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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number', 20)->unique();
            $table->string('vehicle_make', 50);
            $table->string('vehicle_model', 50);
            $table->string('vehicle_color', 30);
            $table->enum('vehicle_type', ['car', 'suv', 'truck', 'van'])->default('car');
            $table->string('rfid_tag', 50)->unique();
            $table->unsignedBigInteger('owner_id');
            $table->datetime('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('plate_number');
            $table->index('rfid_tag');
            $table->index('owner_id');
            $table->index('expires_at');
            $table->index('is_active');
            $table->foreign('owner_id')->references('id')->on('sys_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};