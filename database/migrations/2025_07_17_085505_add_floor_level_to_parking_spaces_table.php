<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parking_spaces', function (Blueprint $table) {
            $table->string('floor_level')->default('4th Floor')->after('distance_cm');
        });
    }

    public function down(): void
    {
        Schema::table('parking_spaces', function (Blueprint $table) {
            $table->dropColumn('floor_level');
        });
    }
};