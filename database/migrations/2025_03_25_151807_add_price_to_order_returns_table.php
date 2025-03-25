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
    Schema::table('order_returns', function (Blueprint $table) {
        $table->decimal('price', 10, 2)->after('status'); // Thêm cột price sau cột status
    });
}

public function down()
{
    Schema::table('order_returns', function (Blueprint $table) {
        $table->dropColumn('price');
    });
}
};
