<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyStatusColumnInOrderReturnsTable extends Migration
{
    public function up()
    {
        Schema::table('order_returns', function (Blueprint $table) {
            // Xóa cột 'status'
            $table->dropColumn('status');

            // Thêm cột 'status_id' làm khóa ngoại
            $table->foreignId('status_id')->nullable()->constrained('order_statuses')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('order_returns', function (Blueprint $table) {
            // Thêm lại cột 'status'
            $table->string('status')->default('pending');

            // Xóa cột 'status_id'
            $table->dropColumn('status_id');
        });
    }
}
