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
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id(); // ID
            $table->unsignedBigInteger('user_id')->comment('ID người dùng liên kết'); // ID người dùng
            $table->text('address')->comment('Địa chỉ đầy đủ của người dùng'); // Địa chỉ đầy đủ
            $table->boolean('id_default')->default(false)->comment('1 nếu là địa chỉ mặc định, 0 nếu không'); // Địa chỉ mặc định
            $table->timestamps(); // created_at, updated_at

            // Khóa ngoại liên kết tới bảng users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
