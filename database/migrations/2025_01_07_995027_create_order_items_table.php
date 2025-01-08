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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id()->comment('ID chi tiết đơn hàng'); // Khóa chính tự động tăng
            $table->unsignedBigInteger('order_id')->comment('ID đơn hàng liên kết'); // ID đơn hàng liên kết
            $table->unsignedBigInteger('product_id')->comment('ID sản phẩm'); // ID sản phẩm
            $table->unsignedBigInteger('product_variant_id')->nullable()->comment('ID biến thể sản phẩm'); // ID biến thể sản phẩm
            $table->string('name')->comment('Tên sản phẩm'); // Tên sản phẩm
            $table->decimal('price', 11, 2)->comment('Giá sản phẩm'); // Giá sản phẩm
            $table->integer('quantity')->comment('Số lượng sản phẩm trong đơn hàng'); // Số lượng sản phẩm
            $table->string('name_variant')->nullable()->comment('Tên biến thể của sản phẩm'); // Tên biến thể sản phẩm
            $table->json('attributes_variant')->nullable()->comment('Thông tin thuộc tính biến thể (dạng JSON)'); // Thông tin thuộc tính biến thể
            $table->decimal('price_variant', 11, 2)->nullable()->comment('Giá của biến thể sản phẩm'); // Giá của biến thể sản phẩm
            $table->integer('quantity_variant')->nullable()->comment('Số lượng của biến thể sản phẩm'); // Số lượng biến thể sản phẩm

            $table->timestamps(); // Thêm cột created_at và updated_at

            // Khóa ngoại cho order_id
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');

            // Khóa ngoại cho product_id
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            // Khóa ngoại cho product_variant_id (nếu có)
            $table->foreign('product_variant_id')->references('id')->on('product_variants');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
