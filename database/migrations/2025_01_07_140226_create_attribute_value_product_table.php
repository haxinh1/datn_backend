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
        Schema::create('attribute_value_product', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id'); // Sản phẩm liên kết
            $table->unsignedBigInteger('attribute_value_id'); // Giá trị thuộc tính liên kết

            // Khóa chính
            $table->primary(['product_id', 'attribute_value_id']);

            // Liên kết tới bảng products và attribute_values
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('attribute_value_id')->references('id')->on('attribute_values')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_value_product');
    }
};
