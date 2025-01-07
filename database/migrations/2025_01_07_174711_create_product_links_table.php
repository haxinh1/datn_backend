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
        Schema::create('product_links', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->comment('ID sản phẩm'); // ID sản phẩm
            $table->unsignedBigInteger('product_link_id')->comment('ID sản phẩm được liên kết (link)'); // ID sản phẩm liên kết

            // Khóa chính tổng hợp
            $table->primary(['product_id', 'product_link_id']);

            // Khóa ngoại cho product_id
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            // Khóa ngoại cho product_link_id
            $table->foreign('product_link_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_links');
    }
};
