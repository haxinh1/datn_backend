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
        Schema::create('category_type_product', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id'); // Liên kết sản phẩm
            $table->unsignedBigInteger('category_type_id'); // Liên kết loại danh mục
            $table->primary(['product_id', 'category_type_id']);
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('category_type_id')->references('id')->on('category_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_type_product');
    }
};
