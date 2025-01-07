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
        Schema::create('coupon_users', function (Blueprint $table) {
            $table->unsignedBigInteger('coupon_id')->comment('ID mã giảm giá'); // ID mã giảm giá
            $table->unsignedBigInteger('user_id')->comment('ID người dùng'); // ID người dùng

            // Khóa chính tổng hợp
            $table->primary(['coupon_id', 'user_id']);

            // Khóa ngoại
            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_users');
    }
};
