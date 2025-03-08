<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('comments');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            // Xóa bảng comments nếu tồn tại



        // Tạo lại bảng comments
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('products_id');
            $table->foreign('products_id')->references('id')->on('products')->onDelete('cascade');
            $table->unsignedBigInteger('users_id');
            $table->foreign('users_id')->references('id')->on('users')->onDelete('cascade');
            $table->text('comments');
            $table->integer('rating')->default(5);
            $table->datetime('comment_date');
            $table->integer('status')->default(1); // 1: Chờ duyệt //  2: đã duyệt, 3: đã ẩn
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->integer('status')->default(1)->change();
        });
    }
};

