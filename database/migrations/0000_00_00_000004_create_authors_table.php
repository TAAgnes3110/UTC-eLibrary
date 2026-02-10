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
            $table->increments('id')->index();
            $table->string('name')->comment('Tên tác giả')->index();
            $table->text('tieu_su')->nullable();
            $table->date('birth_date')->nullable();
            $table->json('params')->nullable();
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
