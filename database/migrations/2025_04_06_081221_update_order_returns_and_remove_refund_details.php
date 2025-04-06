<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Thêm 4 cột vào bảng order_returns
        Schema::table('order_returns', function (Blueprint $table) {
            $table->string('bank_account_number')->nullable()->after('employee_evidence');
            $table->string('bank_name')->nullable()->after('bank_account_number');
            $table->string('bank_qr')->nullable()->after('bank_name');
            $table->text('refund_proof')->nullable()->after('bank_qr'); 
        });

        // Xoá bảng refund_details nếu tồn tại
        Schema::dropIfExists('refund_details');
    }

    public function down(): void
    {
        // Xoá 4 cột đã thêm
        Schema::table('order_returns', function (Blueprint $table) {
            $table->dropColumn([
                'bank_account_number',
                'bank_name',
                'bank_qr',
                'refund_proof', 
            ]);
        });

        // Tạo lại bảng refund_details nếu rollback
        Schema::create('refund_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_return_id');
            $table->text('note')->nullable();
            $table->string('employee_evidence')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });
    }
};
