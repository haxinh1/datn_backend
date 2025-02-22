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
        Schema::create('order_order_statuses', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->comment('ID đơn hàng'); // ID đơn hàng
            $table->unsignedBigInteger('order_status_id')->comment('ID trạng thái đơn hàng'); // ID trạng thái đơn hàng
            $table->unsignedBigInteger('modified_by')->nullable()->comment('ID người cập nhật trạng thái (có thể NULL)');
            $table->string('note', 255)->nullable()->comment('Ghi chú của người xử lý'); // Ghi chú
            $table->json('employee_evidence')->nullable()->comment('Minh chứng của nhân viên'); // Minh chứng của nhân viên
            $table->boolean('customer_confirmation')->nullable()->comment('null nếu nhân viên mới gửi minh chứng, 1 nếu xác nhận đã nhận, 0 nếu xác nhận không nhận được hàng'); // Xác nhận của khách hàng
            $table->timestamps(); // created_at, updated_at

            // Khóa ngoại
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('order_status_id')->references('id')->on('order_statuses')->onDelete('cascade');
            $table->foreign('modified_by')->references('id')->on('users')->onDelete('cascade');

            // Khóa chính tổng hợp
            $table->primary(['order_id', 'order_status_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_order_statuses');
    }
};
