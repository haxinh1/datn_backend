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
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id()->comment('ID phiên trò chuyện'); // ID phiên trò chuyện
            $table->unsignedBigInteger('customer_id')->nullable()->comment('ID khách hàng liên quan'); // ID khách hàng
            $table->unsignedBigInteger('employee_id')->nullable()->comment('ID nhân viên liên quan'); // ID nhân viên
            $table->enum('status', ['open', 'closed'])->default('open')->comment('Trạng thái của phiên trò chuyện'); // Trạng thái
            $table->timestamp('created_date')->nullable()->comment('Thời gian tạo phiên trò chuyện'); // Thời gian tạo
            $table->timestamp('closed_date')->nullable()->comment('Thời gian đóng phiên trò chuyện'); // Thời gian đóng

            // Khóa ngoại
            $table->foreign('customer_id')->references('id')->on('users'); // Liên kết với bảng users
            $table->foreign('employee_id')->references('id')->on('users'); // Liên kết với bảng users

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_sessions');
    }
};
