<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $conditions = ['new', 'good', 'fair', 'poor', 'damaged'];
        $statuses = ['available', 'borrowed', 'reserved', 'maintenance', 'lost'];

        Schema::create('book_copies', function (Blueprint $table) use ($conditions, $statuses) {
            $table->increments('id');
            $table->unsignedInteger('book_id');
            $table->string('barcode')->unique();
            $table->string('call_number')->nullable();
            $table->enum('condition', $conditions)->default('good');
            $table->enum('status', $statuses)->default('available');
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->json('params')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('book_id')->references('id')->on('books')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_copies');
    }
};
