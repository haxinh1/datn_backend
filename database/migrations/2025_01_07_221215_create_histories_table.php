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
        Schema::create('histories', function (Blueprint $table) {
            $table->string('subject_type', 255)->comment('Tên model liên quan'); // Tên model liên quan
            $table->unsignedBigInteger('subject_id')->comment('ID của đối tượng liên quan'); // ID của đối tượng liên quan
            $table->string('action_type', 255)->comment('Loại hành động'); // Loại hành động
            $table->json('old_value')->nullable()->comment('Dữ liệu trước khi thay đổi'); // Dữ liệu trước khi thay đổi
            $table->json('new_value')->nullable()->comment('Dữ liệu sau khi thay đổi'); // Dữ liệu sau khi thay đổi
            $table->unsignedBigInteger('user_id')->comment('ID người dùng thực hiện'); // ID người dùng thực hiện
            $table->text('description')->nullable()->comment('Mô tả chi tiết'); // Mô tả chi tiết
            $table->timestamp('created_at')->useCurrent()->comment('Thời gian tạo bản ghi lịch sử'); // Thời gian tạo bản ghi lịch sử

            // Khóa ngoại liên kết tới bảng users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_history');
    }
};
