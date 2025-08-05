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
        if (!Schema::hasTable('feedbacks'))
        {
            Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('type', ['general', 'bug', 'feature', 'parking']);
            $table->text('message');
            $table->integer('rating')->nullable();
            $table->string('email')->nullable();
            $table->json('issues')->nullable();
            $table->json('device_info')->nullable();
            $table->enum('status', ['pending', 'reviewed', 'resolved'])->default('pending');
            $table->text('admin_response')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('type');
            $table->foreign('user_id')->references('id')->on('sys_users')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('sys_users')->onDelete('set null');
        });
        }
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};