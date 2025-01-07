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
        Schema::create('attributes', function (Blueprint $table) {
            $table->id()->comment('ID thuộc tính'); // ID thuộc tính
            $table->string('name', 255)->comment('Tên thuộc tính'); // Tên thuộc tính
            $table->string('slug', 255)->nullable()->comment('Đường dẫn SEO của thuộc tính'); // Đường dẫn SEO
            $table->boolean('is_variant')->default(0)->comment('1 nếu là thuộc tính của biến thể, 0 nếu là thông số kĩ thuật'); // Loại thuộc tính
            $table->boolean('is_active')->default(1)->comment('1 nếu thuộc tính đang hiển thị, 0 nếu ẩn'); // Trạng thái hiển thị
            $table->timestamps(); // created_at, updated_at
            $table->softDeletes()->comment('Thời gian xóa mềm'); // deleted_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
