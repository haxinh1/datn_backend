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
        Schema::create('refund_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_return_id');
            $table->text('note')->nullable(); // Ghi chú tài khoản ngân hàng
            $table->string('employee_evidence')->nullable(); // Ảnh QR/chuyển khoản
            $table->unsignedTinyInteger('status')->default(12); // 12: yêu cầu hoàn tiền, 13: hoàn tiền thành công
            $table->timestamps();
        
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('order_return_id')->references('id')->on('order_returns')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_details');
    }
};
