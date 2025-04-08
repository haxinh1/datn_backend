<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderReturnsTable extends Migration
{
    public function up()
    {
        Schema::create('order_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');  // Liên kết với bảng orders
            $table->foreignId('product_id')->constrained()->onDelete('cascade');  // Liên kết với bảng products
            $table->foreignId('product_variant_id')->nullable()->constrained()->onDelete('cascade');  // Nếu có biến thể
            $table->integer('quantity_returned');  // Số lượng trả lại
            $table->text('reason');  // Lý do trả lại
            $table->string('employee_evidence')->nullable();  // Video minh chứng hoặc chứng cứ khác
            $table->string('status')->default('pending');  // Trạng thái trả hàng (pending, completed, etc.)
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_returns');
    }
}
