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
        Schema::create('authors', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->comment('Tên tác giả');
            $table->string('pen_name')->nullable()->comment('Bút danh');
            $table->text('biography')->nullable()->comment('Tiểu sử');
            $table->string('nationality')->nullable()->comment('Quốc tịch');
            $table->date('birth_date')->nullable()->comment('Ngày sinh');
            $table->date('death_date')->nullable()->comment('Ngày mất');
            $table->string('email')->nullable()->comment('Email');
            $table->string('website')->nullable()->comment('Website');
            $table->string('avatar')->nullable()->comment('Ảnh đại diện');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('authors');
    }
};
