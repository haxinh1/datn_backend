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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->string('sku',100)->nullable();
            $table->decimal('price',11,2)->comment('Giá nhập của biến thể');
            $table->decimal('sell_price',11,2)->comment('Gía bán của biến thể');
            $table->decimal('sale_price',11,2)->comment('Giá khuyến mãi của biến thể')->nullable();
            $table->timestamp('sale_price_start_at')->nullable();
            $table->timestamp('sale_price_end_at')->nullable();
            $table->string('thumbnail')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
