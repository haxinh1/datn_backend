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
        Schema::create('messages', function (Blueprint $table) {
            $table->id()->comment('ID tin nhắn'); // ID tin nhắn
            $table->unsignedBigInteger('chat_session_id')->comment('ID phiên trò chuyện liên kết'); // ID phiên trò chuyện
            $table->unsignedBigInteger('sender_id')->nullable()->comment('ID người gửi tin nhắn'); // ID người gửi
            $table->text('message')->comment('Nội dung tin nhắn'); // Nội dung tin nhắn
            $table->enum('type', ['text', 'image', 'video', 'file'])->default('text')->comment('Loại tin nhắn (văn bản, hình ảnh, video, tệp)'); // Loại tin nhắn
            $table->boolean('is_read')->default(0)->comment('1: đã đọc tin nhắn, 0: chưa đọc tin nhắn'); // Trạng thái đọc
            $table->timestamp('read_at')->nullable()->comment('Thời gian đọc tin nhắn'); // Thời gian đọc
            $table->timestamps(); // created_at, updated_at

            // Khóa ngoại
            $table->foreign('chat_session_id')->references('id')->on('chat_sessions')->onDelete('cascade');
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
