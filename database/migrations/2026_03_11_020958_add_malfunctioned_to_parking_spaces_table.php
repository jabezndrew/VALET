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
            $table->boolean('malfunctioned')->default(false)->after('override_reason');
            $table->string('malfunction_reason')->nullable()->after('malfunctioned');
            $table->string('malfunction_reported_by')->nullable()->after('malfunction_reason');
            $table->timestamp('malfunctioned_at')->nullable()->after('malfunction_reported_by');
        });
    }

    public function down(): void
    {
        Schema::table('parking_spaces', function (Blueprint $table) {
            $table->dropColumn(['malfunctioned', 'malfunction_reason', 'malfunction_reported_by', 'malfunctioned_at']);
        });
    }
};
