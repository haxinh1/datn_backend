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
        Schema::create('review_multimedia', function (Blueprint $table) {
            $table->id(); // Tạo cột id tự tăng
            $table->unsignedBigInteger('review_id'); // Khóa ngoại review_id
            $table->string('file'); // URL file đa phương tiện
            $table->enum('file_type', ['image', 'video']); // Loại file
        
            // Thiết lập khóa ngoại
            $table->foreign('review_id')->references('id')->on('reviews')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_multimedia');
    }
};
