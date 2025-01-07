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
            $table->id();
            $table->string('code',50)->unique();
            $table->string('title',100)->nullable();
            $table->longText('description')->nullable();
            $table->enum('discount_type',['percent','fix_amount']);
            $table->decimal('discount_value',10,2)->nullable();
            $table->integer('usage_limit')->comment('Số lần sử dụng tối đa')->nullable();
            $table->integer('usage_count')->comment('Số lần mã giảm giá đã được sử dụng')->nullable();
            $table->boolean('is_expired')->comment('1 là mã có hạn, 0 là mã sử dụng vĩnh viễn');
            $table->boolean('is_active')->comment('1 nếu mã đang kích hoạt, 0 nếu không hoạt động');
            $table->timestamp('start_date')->comment('Ngày bắt đầu áp dụng mã giảm giá')->nullable();
            $table->timestamp('end_date')->comment('Ngày kết thúc mã giảm giá')->nullable();
            $table->softDeletes();
            $table->timestamps();
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
