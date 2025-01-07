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
            $table->id();
            $table->string('name',255)->unique();
            $table->string('slug',255)->nullable();
            $table->boolean('is_variant')->default(0)->comment('1 nếu là thuộc tính của biến thể, 0 nếu là thông số kĩ thuật');
            $table->boolean('is_active')->default(0)->comment('1 nếu thuộc tính đang hiển thị, 0 nếu ẩn');
            $table->timestamps();
            $table->softDeletes();
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
