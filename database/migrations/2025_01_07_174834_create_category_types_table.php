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
        Schema::create('category_types', function (Blueprint $table) {
            $table->id()->comment('ID loại danh mục'); // Khóa chính tự động tăng
            $table->unsignedBigInteger('category_id')->comment('ID danh mục liên kết'); // ID danh mục liên kết
            $table->string('icon')->nullable()->comment('Icon loại danh mục'); // Icon loại danh mục
            $table->string('name', 100)->unique()->comment('Tên loại danh mục (duy nhất)'); // Tên loại danh mục
            $table->string('slug', 100)->unique()->comment('Đường dẫn loại danh mục'); // Đường dẫn loại danh mục
            $table->integer('ordinal')->default(0)->comment('Thứ tự hiển thị loại danh mục'); // Thứ tự hiển thị
            $table->tinyInteger('is_active')->default(1)->comment('1 nếu loại danh mục đang hiển thị, 0 nếu ẩn'); // Trạng thái hoạt động
            $table->timestamps(); // Thêm các cột created_at và updated_at

            // Khóa ngoại cho category_id
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_types');
    }
};
