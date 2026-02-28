<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedInteger('book_id');
            $table->foreign('book_id')->references('id')->on('books')->onDelete('cascade');
            $table->enum('status', ['pending', 'fulfilled', 'cancelled', 'expired'])->default('pending')->comment('TT');
            $table->date('reservation_date')->comment('Ngày đặt');
            $table->date('expiry_date')->nullable()->comment('Hạn đặt');
            $table->text('notes')->nullable()->comment('Ghi chú');
            $table->json('params')->nullable()->comment('Tham số');
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
            $table->index(['user_id', 'status']);
            $table->index(['book_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
