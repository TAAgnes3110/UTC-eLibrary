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
            $table->string('code')->unique()->comment('Mã ĐD (MSV/CCCD)')->index();
            $table->string('name')->index()->comment('Họ tên');
            $table->string('email')->unique()->index();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->nullable()->unique()->index()->comment('SĐT');
            $table->enum('user_type', ['SUPER_ADMIN', 'ADMIN', 'LIBRARIAN', 'MEMBER', 'GUEST'])->default('MEMBER')->index()->comment('Loại ND');
            $table->string('avatar')->nullable()->comment('Ảnh');
            $table->date('date_of_birth')->nullable()->comment('Ngày sinh');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->comment('Giới tính');
            $table->text('address')->nullable()->comment('Địa chỉ');
            $table->unsignedInteger('faculty_id')->nullable()->comment('Khoa (SV/GV)');
            $table->unsignedInteger('department_id')->nullable()->comment('Lớp/BM (SV/GV)');
            $table->boolean('is_active')->default(true)->comment('TT hoạt động');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_type', 'is_active']);
            $table->index('faculty_id');
            $table->index('department_id');
            $table->foreign('faculty_id')->references('id')->on('faculties')->onDelete('set null');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
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
