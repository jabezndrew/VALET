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
        Schema::create('parking_spaces', function (Blueprint $table) {
            $table->id();
            $table->integer('sensor_id')->unique();
            $table->boolean('is_occupied')->default(false);
            $table->integer('distance_cm')->nullable();
            $table->string('location')->nullable(); // For future use
            $table->timestamps();
            
            $table->index('sensor_id');
            $table->index('is_occupied');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parking_spaces');
    }
};