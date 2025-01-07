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
        Schema::create('product_accessories', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id'); // Sản phẩm chính
            $table->unsignedBigInteger('accessory_id'); // Phụ kiện
            $table->primary(['product_id', 'accessory_id']);
        
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('accessory_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_accessories');
    }
};
