<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up() {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('total_spent', 10, 2)->default(0)->comment('Tổng tiền đã chi tiêu');
            $table->string('rank')->nullable()->comment('Hạng thành viên');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
