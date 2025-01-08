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
        Schema::create('users', function (Blueprint $table) {
            $table->id()->comment('ID người dùng'); // Khóa chính tự động tăng
            $table->bigInteger('google_id')->nullable()->comment('ID tài khoản Google'); // ID tài khoản Google
            $table->string('phone_number', 20)->unique()->comment('Số điện thoại (duy nhất)'); // Số điện thoại người dùng
            $table->string('email', 100)->unique()->nullable()->comment('Email (duy nhất)'); // Email người dùng
            $table->string('password', 255)->comment('Mật khẩu đã mã hóa'); // Mật khẩu người dùng
            $table->string('fullname', 100)->nullable()->comment('Họ và tên'); // Họ và tên người dùng
            $table->string('avatar', 255)->nullable()->comment('Ảnh đại diện'); // Ảnh đại diện người dùng
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->comment('Giới tính'); // Giới tính
            $table->date('birthday')->nullable()->comment('Ngày sinh'); // Ngày sinh người dùng
            $table->integer('loyalty_points')->default(0)->comment('Điểm thưởng của người dùng'); // Điểm thưởng
            $table->enum('role', ['customer', 'admin', 'manager'])->default('customer')->comment('Vai trò người dùng'); // Vai trò
            $table->enum('status', ['active', 'inactive', 'banned'])->default('active')->comment('Trạng thái tài khoản'); // Trạng thái tài khoản
            $table->string('remember_token', 100)->nullable()->comment('Ghi nhớ token'); // Token ghi nhớ
            $table->timestamp('verified_at')->nullable()->comment('Thời gian xác thực'); // Thời gian xác thực
            $table->timestamps(); // Cột created_at và updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
