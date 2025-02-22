<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chạy migration để cập nhật bảng orders.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Kiểm tra nếu cột is_paid tồn tại thì xóa đi
            if (Schema::hasColumn('orders', 'is_paid')) {
                $table->dropColumn('is_paid');
            }

            // Thêm cột status_id thay thế
            if (!Schema::hasColumn('orders', 'status_id')) {
                $table->unsignedBigInteger('status_id')->nullable()->comment('ID trạng thái đơn hàng');
                $table->foreign('status_id')->references('id')->on('order_statuses')->onDelete('set null');
            }
        });
    }

    /**
     * Hoàn nguyên migration nếu rollback.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Kiểm tra và xóa cột status_id nếu rollback
            if (Schema::hasColumn('orders', 'status_id')) {
                $table->dropForeign(['status_id']);
                $table->dropColumn('status_id');
            }

            // Thêm lại cột is_paid
            if (!Schema::hasColumn('orders', 'is_paid')) {
                $table->boolean('is_paid')->default(0)->comment('1 nếu đã thanh toán, 0 nếu chưa thanh toán');
            }
        });
    }
};
