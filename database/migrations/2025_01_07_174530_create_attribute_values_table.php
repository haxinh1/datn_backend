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
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id()->comment('ID giá trị thuộc tính'); // ID giá trị thuộc tính
            $table->unsignedBigInteger('attribute_id')->index()->comment('ID thuộc tính liên kết');
            $table->string('value', 255)->comment('Giá trị thuộc tính'); // Giá trị thuộc tính
            $table->boolean('is_active')->default(1)->comment('1 nếu giá trị thuộc tính đang hiển thị, 0 nếu ẩn'); // Trạng thái hiển thị
            $table->timestamps(); // created_at, updated_at
            $table->softDeletes()->comment('Thời gian xóa mềm'); // deleted_at

            // Khóa ngoại
            $table->foreign('attribute_id')->references('id')->on('attributes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
    }
};
