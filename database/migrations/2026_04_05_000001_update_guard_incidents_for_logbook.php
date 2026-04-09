<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add 5W1H logbook fields to guard_incidents
        Schema::table('guard_incidents', function (Blueprint $table) {
            $table->timestamp('incident_at')->nullable()->after('floor_level');
            $table->string('involved_party')->nullable()->after('notes');
            $table->string('action_taken')->nullable()->after('involved_party');
        });

        // Remove guard_report feedback records and revert enum
        DB::table('feedbacks')->where('type', 'guard_report')->delete();

        DB::statement("ALTER TABLE feedbacks MODIFY COLUMN type ENUM('general','bug','feature','parking','technical','suggestion') NOT NULL");
    }

    public function down(): void
    {
        Schema::table('guard_incidents', function (Blueprint $table) {
            $table->dropColumn(['incident_at', 'involved_party', 'action_taken']);
        });

        DB::statement("ALTER TABLE feedbacks MODIFY COLUMN type ENUM('general','bug','feature','parking','guard_report') NOT NULL");
    }
};
