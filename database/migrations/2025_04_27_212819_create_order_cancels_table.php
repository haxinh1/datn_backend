<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderCancelsTable extends Migration
{
    public function up()
    {
        Schema::create('order_cancels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('bank_account_number');
            $table->string('bank_name');
            $table->string('bank_qr')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('status_id')->constrained('order_statuses');
            $table->string('refund_proof'); // 🆕 Bắt buộc có minh chứng hoàn tiền
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_cancels');
    }
}
