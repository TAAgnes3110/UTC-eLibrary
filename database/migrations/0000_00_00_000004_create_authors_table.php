<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->index();
            $table->string('nationality')->nullable();
            $table->text('tieu_su')->nullable();
            $table->date('birth_date')->nullable();
            $table->json('params')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authors');
    }
};
