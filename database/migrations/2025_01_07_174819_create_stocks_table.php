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
            $table->bigInteger('user_id')->comment('ID nhân viên nhập hàng');
            $table->decimal('total_amount', 15, 2)->nullable()->comment('Tổng tiền nhập hàng');
            $table->boolean('status')->default(0);
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
