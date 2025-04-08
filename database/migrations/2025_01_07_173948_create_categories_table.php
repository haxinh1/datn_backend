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
        Schema::create('categories', function (Blueprint $table) {
            $table->id()->comment('ID danh mục'); // ID danh mục
            $table->unsignedBigInteger('parent_id')->nullable()->comment('ID danh mục cha'); // ID danh mục cha
            $table->string('name', 100)->unique()->comment('Tên danh mục (duy nhất)'); // Tên danh mục
            $table->string('slug', 100)->unique()->comment('Đường dẫn danh mục'); // Đường dẫn danh mục
            $table->integer('ordinal')->default(0)->comment('Thứ tự hiển thị của danh mục'); // Thứ tự hiển thị
            $table->boolean('is_active')->default(1)->comment('1 là danh mục đang hiển thị, 0 nếu ẩn'); // Trạng thái hiển thị
            $table->timestamps(); // created_at, updated_at
            $table->softDeletes()->comment('Thời gian xóa mềm'); // deleted_at

            // Khóa ngoại
            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
