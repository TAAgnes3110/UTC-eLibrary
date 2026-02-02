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
            $table->unsignedInteger('faculty_id')->nullable();
            $table->foreign('faculty_id')->references('id')->on('faculties')->onDelete('set null');
            $table->unsignedInteger('department_id')->nullable();
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');

            $table->string('name')->index()->comment('Họ và tên');
            $table->string('email')->unique()->index()->comment('Email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->nullable()->unique()->index()->comment('Số điện thoại');

            $table->string('student_code')->nullable()->unique()->comment('Mã sinh viên')->index();
            $table->string('staff_code')->nullable()->unique()->comment('Mã cán bộ')->index();

            $table->enum('user_type', ['student', 'lecturer', 'staff', 'guest'])->default('student')->comment('Loại người dùng');
            $table->string('role')->default('GUEST')->comment('Vai trò hệ thống');

            $table->string('avatar')->nullable()->comment('Ảnh đại diện');
            $table->date('date_of_birth')->nullable()->comment('Ngày sinh');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->comment('Giới tính');
            $table->text('address')->nullable()->comment('Địa chỉ');

            $table->integer('max_borrow_books')->default(5)->comment('Số sách được mượn tối đa');
            $table->integer('max_borrow_days')->default(14)->comment('Số ngày mượn tối đa');
            $table->boolean('is_active')->default(true)->comment('Trạng thái hoạt động');
            $table->date('membership_expiry')->nullable()->comment('Ngày hết hạn thẻ');

            $table->json('params')->nullable()->comment('Tham số bổ sung');
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
