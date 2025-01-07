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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete()
                ->comment('ID sản phẩm');

            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->nullOnDelete()
                ->comment('ID sản phẩm biến thể');

            $table->integer('quantity')->comment('Số lượng thay đổi (+ nhập, - xuất)');

            $table->string('type', 255)->comment('Loại thay đổi import, export, adjustment');
            $table->text('reason')->nullable()->comment('Lý do thay đổi');
            $table->foreignId('user_id')->constrained()->comment('Người thực hiện thay đổi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
