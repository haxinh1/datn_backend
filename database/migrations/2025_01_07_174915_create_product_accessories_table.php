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
        Schema::create('product_accessories', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->comment('ID sản phẩm chính'); // ID sản phẩm chính
            $table->unsignedBigInteger('product_accessory_id')->comment('ID sản phẩm liên kết'); // ID phụ kiện liên kết

            // Khóa chính tổng hợp
            $table->primary(['product_id', 'product_accessory_id']);

            // Khóa ngoại cho product_id
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            // Khóa ngoại cho product_accessory_id
            $table->foreign('product_accessory_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_accessories');
    }
};
