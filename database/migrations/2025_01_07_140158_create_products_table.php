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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('content')->nullable(); // Mô tả sản phẩm
            $table->string('thumbnail')->nullable(); // Ảnh đại diện
            $table->decimal('price', 11, 2); // Giá nhập
            $table->decimal('sell_price', 11, 2); // Giá bán
            $table->decimal('sale_price', 11, 2)->nullable(); // Giá khuyến mãi
            $table->timestamp('sale_price_start_at')->nullable();
            $table->timestamp('sale_price_end_at')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable(); // Thương hiệu
            $table->boolean('is_active')->default(true); // Hiển thị sản phẩm
            $table->timestamps();
            $table->softDeletes();

            // Liên kết tới bảng brands
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
