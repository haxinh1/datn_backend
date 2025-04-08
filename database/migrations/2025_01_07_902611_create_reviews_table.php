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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id()->comment('ID đánh giá'); // Khóa chính tự động tăng
            $table->unsignedBigInteger('product_id')->comment('ID sản phẩm được đánh giá'); // ID sản phẩm liên quan
            $table->unsignedBigInteger('order_id')->comment('ID đơn hàng liên quan'); // ID đơn hàng liên quan
            $table->unsignedBigInteger('user_id')->nullable()->comment('ID người dùng đánh giá'); // ID người dùng đánh giá
            $table->integer('rating')->comment('Số sao đánh giá (1-5)'); // Số sao đánh giá
            $table->text('review_text')->nullable()->comment('Nội dung đánh giá'); // Nội dung đánh giá
            $table->string('reason')->nullable()->comment('Lý do không duyệt đánh giá'); // Lý do không duyệt đánh giá
            $table->tinyInteger('is_active')->default(1)->comment('1: là trạng thái duyệt, 0: là trạng thái không duyệt'); // Trạng thái duyệt
            $table->timestamps(); // Thêm các cột created_at và updated_at

            // Khóa ngoại cho product_id
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            
            // Khóa ngoại cho order_id
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');

            // Khóa ngoại cho user_id (nếu có người dùng)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
