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
        Schema::create('comments', function (Blueprint $table) {
            $table->id()->comment('ID bình luận'); // ID bình luận
            $table->unsignedBigInteger('product_id')->comment('ID sản phẩm được bình luận'); // ID sản phẩm
            $table->unsignedBigInteger('user_id')->comment('ID người dùng bình luận'); // ID người dùng
            $table->text('content')->comment('Nội dung bình luận'); // Nội dung bình luận
            $table->timestamps(); // created_at, updated_at

            // Khóa ngoại
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
