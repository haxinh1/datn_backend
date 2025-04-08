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
        Schema::create('coupon_restrictions', function (Blueprint $table) {
            $table->id()->comment('ID ràng buộc'); // ID ràng buộc
            $table->unsignedBigInteger('coupon_id')->comment('ID mã giảm giá liên kết'); // ID mã giảm giá
            $table->decimal('min_order_value', 10, 2)->nullable()->comment('Giá trị đơn hàng tối thiểu để áp dụng mã giảm giá'); // Giá trị đơn hàng tối thiểu
            $table->decimal('max_discount_value', 10, 2)->nullable()->comment('Giá trị giảm giá tối đa có thể áp dụng'); // Giá trị giảm giá tối đa
            $table->json('valid_categories')->nullable()->comment('Danh sách ID danh mục hợp lệ (dạng JSON)'); // Danh mục hợp lệ
            $table->json('valid_products')->nullable()->comment('Danh sách ID sản phẩm hợp lệ (dạng JSON)'); // Sản phẩm hợp lệ

            // Khóa ngoại
            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_restrictions');
    }
};
