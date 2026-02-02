<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('faculty_id');
            $table->foreign('faculty_id')->references('id')->on('faculties')->onDelete('cascade');
            $table->string('code')->unique()->comment('Mã bộ môn');
            $table->string('name')->comment('Tên bộ môn');
            $table->text('description')->nullable()->comment('Mô tả');
            $table->string('head_name')->nullable()->comment('Tên trưởng bộ môn');
            $table->string('phone')->nullable()->comment('Số điện thoại');
            $table->string('email')->nullable()->comment('Email');
            $table->boolean('is_active')->default(true)->comment('Trạng thái hoạt động');
            $table->json('params')->nullable()->comment('Tham số bổ sung');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
