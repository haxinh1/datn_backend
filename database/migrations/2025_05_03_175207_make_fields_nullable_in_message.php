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
        Schema::table('messages', function (Blueprint $table) {
            $table->text('message')->nullable()->change();
            $table->string('type')->nullable()->change();
            $table->boolean('is_read')->nullable()->change();
            $table->string('sender_type')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->text('message')->nullable(false)->change();
            $table->string('type')->nullable(false)->change();
            $table->boolean('is_read')->nullable(false)->change();
            $table->string('sender_type')->nullable(false)->change();
        });
    }
};
