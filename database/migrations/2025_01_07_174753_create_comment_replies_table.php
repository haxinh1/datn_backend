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
        Schema::create('comment_replies', function (Blueprint $table) {
            $table->id()->comment('ID trả lời bình luận'); // Khóa chính tự động tăng
            $table->unsignedBigInteger('comment_id')->comment('ID của bình luận cha'); // ID bình luận cha
            $table->unsignedBigInteger('user_id')->comment('ID của người bình luận'); // ID người bình luận
            $table->unsignedBigInteger('reply_user_id')->comment('ID của người được trả lời'); // ID người được trả lời
            $table->text('content')->comment('Nội dung bình luận'); // Nội dung bình luận
            $table->timestamps(); // Thêm các cột created_at và updated_at

            // Khóa ngoại cho comment_id (bình luận cha)
            $table->foreign('comment_id')->references('id')->on('comments')->onDelete('cascade');

            // Khóa ngoại cho user_id (người bình luận)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Khóa ngoại cho reply_user_id (người được trả lời)
            $table->foreign('reply_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_replies');
    }
};
