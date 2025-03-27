<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('used_points')->default(0)->comment('Số điểm khách hàng đã sử dụng để giảm giá')->after('total_amount');
            $table->decimal('discount_points', 15, 2)->default(0)->comment('Số tiền giảm giá từ điểm thưởng')->after('used_points');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['used_points', 'discount_points']);
        });
    }
};
