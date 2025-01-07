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
        Schema::create('category__products', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id'); // Danh mục liên kết
            $table->unsignedBigInteger('product_id'); // Sản phẩm liên kết

            // Khóa chính
            $table->primary(['category_id', 'product_id']);

            // Liên kết tới bảng categories và products
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category__products');
    }
};
