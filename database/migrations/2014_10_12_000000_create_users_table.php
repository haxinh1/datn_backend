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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('google_id')->nullable();
            $table->string('phone_number')->unique();
            $table->string('email',100)->unique();
            $table->string('password');
            $table->string('fullname')->nullable();
            $table->string('avatar')->nullable();
            $table->enum('gender',['male','female','other'])->default('other');
            $table->date('birthday')->nullable();
            $table->integer('loyalty_points')->default(0);
            $table->enum('role',['admin','employee','customer'])->default('customer');
            $table->enum('status',['active','inactive','lock'])->default('active');
            $table->timestamp('verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
