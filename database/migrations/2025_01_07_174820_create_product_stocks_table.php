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
        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_id'); // Biến thể
            $table->unsignedBigInteger('product_id')->nullable(); // Sản phẩm
            $table->unsignedBigInteger('product_variant_id')->nullable(); // Biến thể
            $table->integer('quantity'); // Số lượng
            $table->decimal('price', 11, 2); // Giá nhập
            $table->decimal('sell_price', 11, 2)->nullable()->comment('Giá bán mới'); // Giá bán mới
            $table->decimal('sale_price', 11, 2)->nullable()->comment('Giá khuyến mại mới'); // Giá khuyến mại mới
            $table->timestamps();
        
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
    }
};
