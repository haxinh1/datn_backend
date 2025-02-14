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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            // $table->bigInteger('supplier_id')->comment('ID nhà cung cấp');
            $table->decimal('total_amount', 15, 2)->nullable()->comment('Tổng tiền nhập hàng');
            $table->boolean('status')->default(0);
            $table->bigInteger('created_by')->nullable()->comment('ID nhân viên tạo nhập hàng');
            $table->bigInteger('updated_by')->nullable()->comment('ID nhân viên cập nhập');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
