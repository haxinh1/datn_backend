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
        Schema::table('messages', function (Blueprint $table) {
            $table->string('guest_phone')->nullable()->after('sender_id')->comment('Số điện thoại khách vãng lai');
            $table->string('guest_name')->nullable()->after('guest_phone')->comment('Tên khách vãng lai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['guest_phone', 'guest_name']);
        });
    }
};
