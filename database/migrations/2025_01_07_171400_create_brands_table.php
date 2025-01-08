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
        Schema::create('brands', function (Blueprint $table) {
            $table->id(); // Tự động thêm trường 'id' kiểu BIGINT AUTO_INCREMENT
            $table->string('name', 100)->unique()->comment('Tên thương hiệu (duy nhất)');
            $table->string('slug', 100)->unique()->comment('Đường dẫn thương hiệu (SEO)');
            $table->string('logo', 255)->nullable()->comment('Logo thương hiệu');
            $table->boolean('is_active')->default(true)->comment('1 nếu thương hiệu đang hiển thị, 0 nếu ẩn');
            $table->timestamps(); // Thêm 'created_at' và 'updated_at'
            $table->softDeletes()->comment('Thời gian xóa mềm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
