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
            $table->id(); // ID sản phẩm
            $table->unsignedBigInteger('brand_id')->nullable()->comment('ID thương hiệu'); // ID thương hiệu
            $table->string('name', 250)->comment('Tên sản phẩm'); // Tên sản phẩm
            $table->string('slug', 250)->nullable()->comment('Đường dẫn sản phẩm (SEO)'); // Đường dẫn SEO
            $table->integer('views')->default(0)->comment('Số lượt xem sản phẩm'); // Số lượt xem
            $table->text('content')->nullable()->comment('Mô tả chi tiết sản phẩm'); // Mô tả chi tiết
            $table->string('thumbnail', 255)->comment('Ảnh đại diện của sản phẩm'); // Ảnh đại diện
            $table->string('sku', 255)->nullable()->comment('Mã SKU của sản phẩm'); // Mã SKU
            $table->integer('stock')->default(0); //số lượng tồn kho
            $table->decimal('sell_price', 11, 2)->nullable()->comment('Giá bán sản phẩm'); // Giá bán
            $table->decimal('sale_price', 11, 2)->nullable()->comment('Giá giảm khuyến mãi'); // Giá giảm khuyến mãi
            $table->timestamp('sale_price_start_at')->nullable()->comment('Thời gian bắt đầu giá sale sản phẩm'); // Thời gian bắt đầu sale
            $table->timestamp('sale_price_end_at')->nullable()->comment('Thời gian kết thúc giá sale sản phẩm'); // Thời gian kết thúc sale
            $table->boolean('is_active')->default(1)->comment('1 nếu sản phẩm đang hiển thị, 0 nếu ẩn'); // Trạng thái hiển thị
            $table->timestamps(); // created_at, updated_at
            $table->softDeletes()->comment('Thời gian xóa mềm'); // deleted_at

            // Khóa ngoại liên kết tới bảng brands
            $table->foreign('brand_id')->references('id')->on('brands');
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
