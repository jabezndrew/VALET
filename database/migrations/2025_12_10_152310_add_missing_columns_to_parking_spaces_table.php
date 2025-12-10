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
            if (!Schema::hasColumn('parking_spaces', 'slot_name')) {
                $table->string('slot_name')->nullable()->after('sensor_id');
            }
            if (!Schema::hasColumn('parking_spaces', 'section')) {
                $table->string('section')->nullable()->after('slot_name');
            }
            if (!Schema::hasColumn('parking_spaces', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_occupied');
            }
            if (!Schema::hasColumn('parking_spaces', 'x_position')) {
                $table->decimal('x_position', 8, 2)->nullable()->after('floor_level');
            }
            if (!Schema::hasColumn('parking_spaces', 'y_position')) {
                $table->decimal('y_position', 8, 2)->nullable()->after('x_position');
            }
            if (!Schema::hasColumn('parking_spaces', 'rotation')) {
                $table->integer('rotation')->default(0)->after('y_position');
            }
            if (!Schema::hasColumn('parking_spaces', 'width')) {
                $table->decimal('width', 8, 2)->default(60)->after('rotation');
            }
            if (!Schema::hasColumn('parking_spaces', 'height')) {
                $table->decimal('height', 8, 2)->default(85)->after('width');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parking_spaces', function (Blueprint $table) {
            $table->dropColumn([
                'slot_name',
                'section',
                'is_active',
                'x_position',
                'y_position',
                'rotation',
                'width',
                'height'
            ]);
        });
    }
};
