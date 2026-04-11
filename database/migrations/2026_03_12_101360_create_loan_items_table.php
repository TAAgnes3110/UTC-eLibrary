<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('books')->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->enum('condition_on_loan', ['tot', 'hong', 'mat'])->nullable();
            $table->enum('condition_on_return', ['tot', 'hong', 'mat'])->nullable();
            $table->decimal('fine_amount', 12, 2)->default(0);
            $table->text('notes')->nullable()->comment('Ghi chú thủ thư');

            $table->timestamps();

            $table->index(['loan_id', 'book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_items');
    }
};
