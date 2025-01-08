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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id()->comment('ID giỏ hàng'); // ID giỏ hàng
            $table->unsignedBigInteger('user_id')->comment('ID người dùng liên kết'); // ID người dùng
            $table->unsignedBigInteger('product_id')->nullable()->comment('ID sản phẩm'); // ID sản phẩm
            $table->unsignedBigInteger('product_variant_id')->nullable()->comment('ID biến thể sản phẩm'); // ID biến thể sản phẩm
            $table->integer('quantity')->default(1)->comment('Số lượng sản phẩm trong giỏ hàng'); // Số lượng sản phẩm
            $table->timestamps(); // created_at, updated_at

            // Khóa ngoại
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('product_variant_id')->references('id')->on('product_variants');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
