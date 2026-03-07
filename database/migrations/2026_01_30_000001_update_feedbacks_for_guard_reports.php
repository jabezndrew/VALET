<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if foreign key exists before dropping
        $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'feedbacks' AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = 'feedbacks_user_id_foreign'");

        if (count($foreignKeys) > 0) {
            Schema::table('feedbacks', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        // Make user_id nullable for guard reports
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        // Check if foreign key already exists before adding
        $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'feedbacks' AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = 'feedbacks_user_id_foreign'");

        if (count($foreignKeys) === 0) {
            Schema::table('feedbacks', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('sys_users')->onDelete('set null');
            });
        }

        // Update the type enum to include guard_report
        DB::statement("ALTER TABLE feedbacks MODIFY COLUMN type ENUM('general', 'bug', 'feature', 'parking', 'guard_report') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert type enum
        DB::statement("ALTER TABLE feedbacks MODIFY COLUMN type ENUM('general', 'bug', 'feature', 'parking') NOT NULL");

        // Drop foreign key
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        // Make user_id not nullable again
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        // Re-add original foreign key
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('sys_users')->onDelete('cascade');
        });
    }
};
