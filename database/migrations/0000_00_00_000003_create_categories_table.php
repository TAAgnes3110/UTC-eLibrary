<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->unique()->comment('Mã danh mục');
            $table->string('name')->comment('Tên danh mục');
            $table->text('description')->nullable()->comment('Mô tả');
            $table->unsignedInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('cascade');
            $table->integer('order')->default(0)->comment('Thứ tự hiển thị');
            $table->boolean('is_active')->default(true)->comment('Trạng thái hoạt động');
            $table->json('params')->nullable()->comment('Tham số bổ sung');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
