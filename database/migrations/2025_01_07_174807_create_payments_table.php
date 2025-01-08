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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('payments')->onDelete('set null')->comment('ID phương thức thanh toán cha (nếu có)');
            $table->string('name', 255)->comment('Tên phương thức thanh toán');
            $table->string('logo', 255)->nullable()->comment('Logo phương thức thanh toán');
            $table->boolean('is_active')->default(true)->comment('1 nếu đang kích hoạt, 0 nếu không');
            $table->timestamps();
            $table->softDeletes()->comment('Thời gian xóa mềm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
