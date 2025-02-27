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
        Schema::table('order_order_statuses', function (Blueprint $table) {
            $table->text('employee_evidence')->nullable()->change(); // Chuyển từ JSON sang TEXT
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_order_statuses', function (Blueprint $table) {
            $table->json('employee_evidence')->nullable()->change(); // Khôi phục lại kiểu JSON nếu rollback
        });
    }
};
