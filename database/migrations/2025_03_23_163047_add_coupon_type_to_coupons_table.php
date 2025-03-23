<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            // Thêm cột rank với ENUM chứa các cấp bậc
            $table->enum('rank', ['bronze', 'silver', 'gold', 'diamond'])->default('bronze')->after('end_date');

            // Thêm cột coupon_type
            $table->enum('coupon_type', ['public', 'private', 'rank'])->default('public')->after('rank');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            // Xóa cột coupon_type
            $table->dropColumn('coupon_type');

            // Xóa cột rank
            $table->dropColumn('rank');
        });
    }
};
