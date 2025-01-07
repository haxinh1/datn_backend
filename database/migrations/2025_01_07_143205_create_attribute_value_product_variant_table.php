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
        Schema::create('attribute_value_product_variant', function (Blueprint $table) {
            $table->unsignedBigInteger('product_variant_id'); // Liên kết biến thể sản phẩm
            $table->unsignedBigInteger('attribute_value_id'); // Liên kết giá trị thuộc tính
            $table->primary(['product_variant_id', 'attribute_value_id']);
    
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('cascade');
            $table->foreign('attribute_value_id')->references('id')->on('attribute_values')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_value_product_variant');
    }
};
