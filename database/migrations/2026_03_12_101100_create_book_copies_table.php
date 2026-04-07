<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->string('barcode')->nullable()->unique();
            $table->string('call_number')->nullable();
            $table->unsignedTinyInteger('status')->default(1)->index()->comment('BookStatus: 1 available, 2 borrowed, …');
            $table->string('physical_condition', 24)->default('good')->index()->comment('Vật lý: good, fair, worn, needs_repair, damaged');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('location', 100)->nullable();
            $table->json('params')->nullable();

            $table->userAuditColumns();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['book_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_copies');
    }
};
