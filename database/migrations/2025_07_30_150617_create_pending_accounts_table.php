<?php
// database/migrations/xxxx_xx_xx_create_pending_accounts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['user', 'security', 'ssd', 'admin'])->default('user');
            $table->string('employee_id')->nullable();
            $table->string('department')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by'); // SSD who created it
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable(); // Admin who reviewed
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            
            $table->foreign('created_by')->references('id')->on('sys_users')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('sys_users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_accounts');
    }
};