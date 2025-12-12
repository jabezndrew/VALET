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
        Schema::create('parking_floors', function (Blueprint $table) {
            $table->id();
            $table->integer('floor_number')->unique();
            $table->string('floor_name', 50);
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->index('floor_number');
            $table->index('is_active');
        });

        Schema::create('parking_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('floor_id')->constrained('parking_floors')->onDelete('cascade');
            $table->string('column_code', 5);
            $table->string('column_name', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->unique(['floor_id', 'column_code']);
            $table->index(['floor_id', 'is_active']);
        });

        Schema::create('parking_config', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value');
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parking_config');
        Schema::dropIfExists('parking_columns');
        Schema::dropIfExists('parking_floors');
    }
};
