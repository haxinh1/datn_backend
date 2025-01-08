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
        Schema::create('order_statuses', function (Blueprint $table) {
            $table->id()->comment('ID trạng thái sản phẩm'); // ID trạng thái sản phẩm
            $table->string('name', 255)->comment('Tên trạng thái'); // Tên trạng thái
            $table->integer('ordinal')->default(0)->comment('Sắp xếp thứ tự'); // Thứ tự sắp xếp
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_statuses');
    }
};
