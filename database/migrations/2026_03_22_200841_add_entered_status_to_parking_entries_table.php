<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE parking_entries MODIFY COLUMN status ENUM('entered', 'parked', 'exited') NOT NULL DEFAULT 'entered'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE parking_entries MODIFY COLUMN status ENUM('parked', 'exited') NOT NULL DEFAULT 'parked'");
    }
};
