<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('comment_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('comment_id');
            $table->string('image'); // Lưu đường dẫn ảnh
            $table->timestamps();

            $table->foreign('comment_id')->references('id')->on('comments')->onDelete('cascade'); // Nếu xóa comment thì ảnh cũng bị xóa
        });
    }

    public function down()
    {
        Schema::dropIfExists('comment_images');
    }

};
