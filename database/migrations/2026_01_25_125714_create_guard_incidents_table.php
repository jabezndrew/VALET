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
        Schema::create('guard_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('space_code')->nullable();
            $table->string('floor_level');
            $table->enum('category', ['debris', 'damaged', 'blocked', 'light_issue', 'sensor_issue', 'other']);
            $table->text('notes')->nullable();
            $table->enum('status', ['open', 'in_progress', 'resolved'])->default('open');
            $table->string('reported_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolved_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guard_incidents');
    }
};
