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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id()->comment('ID'); // Khóa chính tự động tăng
            $table->unsignedBigInteger('product_id')->nullable()->comment('ID sản phẩm'); // ID sản phẩm
            $table->unsignedBigInteger('product_variant_id')->nullable()->comment('ID sản phẩm biến thể'); // ID sản phẩm biến thể
            $table->integer('quantity')->comment('Số lượng thay đổi (+ nhập, - xuất)'); // Số lượng thay đổi
            $table->string('type')->comment('Loại thay đổi import, export, adjustment'); // Loại thay đổi
            $table->text('reason')->nullable()->comment('Lý do thay đổi'); // Lý do thay đổi
            $table->unsignedBigInteger('user_id')->comment('Người thực hiện thay đổi'); // Người thực hiện thay đổi
            $table->timestamps(); // Cột created_at và updated_at

            // Khóa ngoại cho product_id
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');

            // Khóa ngoại cho product_variant_id (nếu có)
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('set null');

            // Khóa ngoại cho user_id
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
