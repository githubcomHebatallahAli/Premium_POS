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
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('address');
            $table->string('phoNum');
            $table->foreignId('role_id')->nullable()->constrained('roles')->cascadeOnDelete();
            $table->enum('status', ['active', 'notActive'])->default('active');
            $table->string('image')->nullable();
            $table->string('salary');
            $table->timestamp('creationDate')->nullable();
            $table->string('ip')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_logout_at')->nullable();
            $table->integer('session_duration')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }


};
