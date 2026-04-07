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
            $table->string('code')->unique();
            $table->string('name')->index();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->nullable()->unique()->comment('Số điện thoại');
            $table->string('user_type', 20)->default('MEMBER')->index()->comment('MEMBER|STUDENT|TEACHER|LIBRARIAN|ADMIN|EXTERNAL|…');
            $table->string('avatar')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 10)->nullable();
            $table->text('address')->nullable();
            $table->unsignedInteger('faculty_id')->nullable()->comment('Khoa');
            $table->unsignedInteger('department_id')->nullable()->comment('Bộ môn/Trường con');
            $table->string('cohort', 20)->nullable()->index()->comment('Niên khóa/Khoá');
            $table->unsignedInteger('period_id')->nullable()->comment('Niên khóa (bảng periods)');
            $table->string('class_code', 100)->nullable()->comment('Lớp hành chính');
            $table->boolean('is_active')->default(true);

            $table->userAuditColumns();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_type', 'is_active']);
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
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
