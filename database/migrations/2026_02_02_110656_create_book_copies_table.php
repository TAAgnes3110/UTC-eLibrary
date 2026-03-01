<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_copies', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('book_id');
            $table->string('barcode')->unique();
            $table->string('call_number')->nullable();
            $table->string('condition', 20)->default('good');
            $table->string('status', 20)->default('available')->index();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->json('params')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['book_id', 'status']);
            $table->foreign('book_id')->references('id')->on('books')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_copies');
    }
};
