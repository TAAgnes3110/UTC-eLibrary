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
            $table->foreign('book_id')->references('id')->on('books')->onDelete('cascade');
            $table->string('barcode')->unique()->comment('Mã vạch');
            $table->string('call_number')->nullable()->comment('Ký hiệu cá biệt');
            $table->enum('condition', ['new', 'good', 'fair', 'poor', 'damaged'])->default('good')->comment('Tình trạng');
            $table->enum('status', ['available', 'borrowed', 'reserved', 'maintenance', 'lost'])->default('available')->comment('Trạng thái');
            $table->string('location')->nullable()->comment('Vị trí (Kệ, Tầng)');
            $table->text('notes')->nullable()->comment('Ghi chú');
            $table->json('params')->nullable()->comment('Tham số bổ sung');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_copies');
    }
};
