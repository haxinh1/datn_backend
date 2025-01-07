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
        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable()->comment('ID sản phẩm'); 
            $table->unsignedBigInteger('product_variant_id')->nullable()->comment('ID sản phẩm biến thể');
            $table->bigInteger('stock')->default(0)->comment('Số lượng tồn kho');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
    }
};
