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
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->string('ProvinceID')->after('address')->nullable();
            $table->string('DistrictID')->after('ProvinceID')->nullable(); 
            $table->string('WardCode')->after('DistrictID')->nullable(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->dropColumn(['ProvinceID', 'DistrictID', 'WardCode']);
        });
    }
};