<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publishers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->comment('Tên nhà xuất bản');
            $table->string('code')->unique()->nullable()->comment('Mã NXB');
            $table->text('address')->nullable()->comment('Địa chỉ');
            $table->string('phone')->nullable()->comment('Số điện thoại');
            $table->string('email')->nullable()->comment('Email');
            $table->string('website')->nullable()->comment('Website');
            $table->string('contact_person')->nullable()->comment('Người liên hệ');
            $table->string('country')->default('Việt Nam')->comment('Quốc gia');
            $table->boolean('is_active')->default(true)->comment('Trạng thái hoạt động');
            $table->json('params')->nullable()->comment('Tham số bổ sung');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publishers');
    }
};
