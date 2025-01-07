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
        Schema::create('orders', function (Blueprint $table) {
            $table->id()->comment('ID đơn hàng'); // ID đơn hàng
            $table->string('code', 50)->unique()->comment('Mã đơn hàng (duy nhất)'); // Mã đơn hàng
            $table->unsignedBigInteger('user_id')->nullable()->comment('ID người dùng đặt hàng'); // ID người dùng
            $table->unsignedBigInteger('payment_id')->nullable()->comment('ID phương thức thanh toán'); // ID phương thức thanh toán
            $table->string('phone_number', 20)->nullable()->comment('Số điện thoại liên lạc của người mua'); // Số điện thoại
            $table->string('email', 255)->nullable()->comment('Email liên lạc của người mua'); // Email
            $table->string('fullname', 255)->nullable()->comment('Họ và tên của người nhận'); // Họ và tên
            $table->string('address', 255)->nullable()->comment('Địa chỉ giao hàng'); // Địa chỉ giao hàng
            $table->decimal('total_amount', 12, 2)->comment('Tổng tiền thanh toán cho đơn hàng'); // Tổng tiền thanh toán
            $table->boolean('is_paid')->default(0)->comment('1 nếu đã thanh toán, 0 nếu chưa thanh toán'); // Trạng thái thanh toán
            $table->unsignedBigInteger('coupon_id')->nullable()->comment('ID mã giảm giá'); // ID mã giảm giá
            $table->string('coupon_code', 50)->nullable()->comment('Code mã giảm giá'); // Mã giảm giá
            $table->string('coupon_description', 50)->nullable()->comment('Mô tả giảm giá'); // Mô tả mã giảm giá
            $table->string('coupon_discount_type', 50)->nullable()->comment('Loại giảm giá'); // Loại giảm giá
            $table->string('coupon_discount_value', 50)->nullable()->comment('Giá trị giảm của mã giảm giá'); // Giá trị giảm giá
            $table->timestamps(); // created_at, updated_at

            // Khóa ngoại
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('set null');
            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
