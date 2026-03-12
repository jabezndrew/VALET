<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Make message nullable
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->text('message')->nullable()->change();
        });

        // Expand the enum to include all used values
        DB::statement("ALTER TABLE feedbacks MODIFY COLUMN type ENUM('general','bug','feature','parking','technical','suggestion','guard_report') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE feedbacks MODIFY COLUMN type ENUM('general','bug','feature','parking') NOT NULL");

        Schema::table('feedbacks', function (Blueprint $table) {
            $table->text('message')->nullable(false)->change();
        });
    }
};
