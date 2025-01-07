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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained();
            $table->string('name',250);
            $table->string('name_link')->nullable();
            $table->string('slug');
            $table->integer('views')->default(0);
            $table->longText('content')->nullable();
            $table->string('thumbnail',250);
            $table->string('sku',100)->nullable();
            $table->decimal('price',11,2)->comment('Giá nhập sản phẩm')->nullable();
            $table->decimal('sell_price',11,2)->nullable();
            $table->decimal('sale_price',11,2)->nullable();
            $table->timestamp('sale_price_start_at')->comment('Thời gian bắt đầu giá sale sản phẩm')->nullable();
            $table->timestamp('sale_price_end_at')->comment('Thời gian kết thúc giá sale sản phẩm')->nullable();
            $table->boolean('is_active')->comment('1 nếu sản phẩm đang hiển thị, 0 nếu ẩn')->default(1);
            $table->softDeletes()->comment('Thời gian xóa mềm');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
