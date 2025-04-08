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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id'); // Sản phẩm liên kết
            $table->string('sku')->nullable(); // Mã SKU
            $table->decimal('sell_price', 11, 2); // Giá bán
            $table->decimal('sale_price', 11, 2)->nullable(); // Giá khuyến mãi
            $table->integer('stock')->default(0); //số lượng tồn kho
            $table->timestamp('sale_price_start_at')->nullable();
            $table->timestamp('sale_price_end_at')->nullable();
            $table->string('thumbnail')->nullable(); // Ảnh biến thể
            $table->boolean('is_active')->default(1)->comment('1 nếu sản phẩm đang hiển thị, 0 nếu ẩn');
            $table->timestamps();
            $table->softDeletes();

            // Liên kết tới bảng products
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
