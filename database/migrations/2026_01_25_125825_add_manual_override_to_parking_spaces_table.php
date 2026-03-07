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
            if (!Schema::hasColumn('parking_spaces', 'manual_override')) {
                $table->boolean('manual_override')->default(false);
            }
            if (!Schema::hasColumn('parking_spaces', 'manual_status')) {
                $table->enum('manual_status', ['occupied', 'available', 'blocked'])->nullable();
            }
            if (!Schema::hasColumn('parking_spaces', 'manual_override_at')) {
                $table->timestamp('manual_override_at')->nullable();
            }
            if (!Schema::hasColumn('parking_spaces', 'manual_override_expires')) {
                $table->timestamp('manual_override_expires')->nullable();
            }
            if (!Schema::hasColumn('parking_spaces', 'manual_override_by')) {
                $table->string('manual_override_by')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parking_spaces', function (Blueprint $table) {
            $table->dropColumn(['manual_override', 'manual_status', 'manual_override_at', 'manual_override_expires', 'manual_override_by']);
        });
    }
};
