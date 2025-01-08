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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id()->comment('ID mã giảm giá'); // ID mã giảm giá
            $table->string('code', 50)->unique()->comment('Mã giảm giá (duy nhất)'); // Mã giảm giá
            $table->string('title', 50)->nullable()->comment('Tiêu đề của mã giảm giá'); // Tiêu đề mã giảm giá
            $table->string('description', 255)->nullable()->comment('Mô tả chi tiết của mã giảm giá'); // Mô tả chi tiết
            $table->enum('discount_type', ['percent', 'fix_amount'])->default('percent')->comment('Kiểu giảm giá (phần trăm hoặc số tiền cố định)'); // Kiểu giảm giá
            $table->decimal('discount_value', 10, 2)->comment('Giá trị giảm giá áp dụng'); // Giá trị giảm giá
            $table->integer('usage_limit')->nullable()->comment('Số lần sử dụng tối đa'); // Số lần sử dụng tối đa
            $table->integer('usage_count')->default(0)->comment('Số lần mã giảm giá đã được sử dụng'); // Số lần đã sử dụng
            $table->boolean('is_expired')->default(0)->comment('1 là mã có hạn, 0 là mã sử dụng vĩnh viễn'); // Trạng thái hết hạn
            $table->boolean('is_active')->default(1)->comment('1 nếu mã đang kích hoạt, 0 nếu không hoạt động'); // Trạng thái hoạt động
            $table->timestamp('start_date')->nullable()->comment('Ngày bắt đầu áp dụng mã giảm giá'); // Ngày bắt đầu
            $table->timestamp('end_date')->nullable()->comment('Ngày kết thúc áp dụng mã giảm giá'); // Ngày kết thúc
            $table->timestamps(); // created_at, updated_at
            $table->softDeletes()->comment('Thời gian xóa mềm'); // deleted_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
