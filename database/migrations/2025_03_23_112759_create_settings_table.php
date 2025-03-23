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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('parent_id')->nullable();
            $table->string('name',255);
            $table->string('slug',255)->nullable();
            $table->text('description')->nullable();
            $table->text('content')->nullable();
            $table->boolean('active')->default(1);
            $table->integer('order')->nullable();
            $table->string('image_path')->nullable();
            $table->string('banner')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
