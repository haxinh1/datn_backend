<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Chạy migration để cập nhật bảng order_items.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Kiểm tra nếu chưa có cột sell_price thì mới thêm
            if (!Schema::hasColumn('order_items', 'sell_price')) {
                $table->decimal('sell_price', 11, 2)->nullable()->after('product_id');
            }
        });

        // Kiểm tra nếu cột price tồn tại trước khi chạy UPDATE
        if (Schema::hasColumn('order_items', 'price')) {
            DB::statement('UPDATE order_items SET sell_price = price');
        }

        Schema::table('order_items', function (Blueprint $table) {
            // Kiểm tra và chỉ xóa cột nếu nó tồn tại
            if (Schema::hasColumn('order_items', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('order_items', 'name_variant')) {
                $table->dropColumn('name_variant');
            }
            if (Schema::hasColumn('order_items', 'attributes_variant')) {
                $table->dropColumn('attributes_variant');
            }
            if (Schema::hasColumn('order_items', 'price_variant')) {
                $table->dropColumn('price_variant');
            }
            if (Schema::hasColumn('order_items', 'quantity_variant')) {
                $table->dropColumn('quantity_variant');
            }
            if (Schema::hasColumn('order_items', 'price')) {
                $table->dropColumn('price');
            }
        });
    }

    /**
     * Hoàn nguyên migration nếu rollback.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Kiểm tra nếu cột price chưa có thì thêm lại
            if (!Schema::hasColumn('order_items', 'price')) {
                $table->decimal('price', 11, 2)->nullable()->after('product_id');
            }
        });

        // Chuyển dữ liệu từ 'sell_price' về 'price'
        if (Schema::hasColumn('order_items', 'sell_price')) {
            DB::statement('UPDATE order_items SET price = sell_price');
        }

        Schema::table('order_items', function (Blueprint $table) {
            // Thêm lại các cột đã xóa nếu rollback
            if (!Schema::hasColumn('order_items', 'name')) {
                $table->string('name')->nullable();
            }
            if (!Schema::hasColumn('order_items', 'name_variant')) {
                $table->string('name_variant')->nullable();
            }
            if (!Schema::hasColumn('order_items', 'attributes_variant')) {
                $table->json('attributes_variant')->nullable();
            }
            if (!Schema::hasColumn('order_items', 'price_variant')) {
                $table->decimal('price_variant', 11, 2)->nullable();
            }
            if (!Schema::hasColumn('order_items', 'quantity_variant')) {
                $table->integer('quantity_variant')->nullable();
            }

            // Kiểm tra và xóa cột sell_price nếu rollback
            if (Schema::hasColumn('order_items', 'sell_price')) {
                $table->dropColumn('sell_price');
            }
        });
    }
};
