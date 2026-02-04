<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->unique()->comment('Mã định danh (MSV/CCCD/Mã cán bộ)')->index();
            $table->string('name')->index()->comment('Họ và tên');
            $table->string('email')->unique()->index()->comment('Email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->nullable()->unique()->index()->comment('Số điện thoại');
            $table->enum('user_type', ['SUPER_ADMIN', 'ADMIN', 'LIBRARIAN', 'MEMBER', 'GUEST'])->default('MEMBER')->index()->comment('Loại người dùng');
            $table->string('avatar')->nullable()->comment('Ảnh đại diện');
            $table->date('date_of_birth')->nullable()->comment('Ngày sinh');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->comment('Giới tính');
            $table->text('address')->nullable()->comment('Địa chỉ');
            $table->boolean('is_active')->default(true)->comment('Trạng thái hoạt động');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
