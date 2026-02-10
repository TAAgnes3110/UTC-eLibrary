<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculties', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->unique()->comment('Mã khoa');
            $table->string('name')->comment('Tên khoa');
            $table->boolean('is_active')->default(true)->comment('Trạng thái hoạt động');
            $table->json('params')->nullable()->comment('Tham số bổ sung');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculties');
    }
};
