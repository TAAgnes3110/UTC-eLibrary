<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_statistics', function (Blueprint $table) {
            $table->increments('id');
            $table->date('stat_date')->unique();
            $table->unsignedInteger('total_books')->default(0);
            $table->unsignedInteger('total_copies')->default(0);
            $table->unsignedInteger('available_copies')->default(0);
            $table->unsignedInteger('borrowed_copies')->default(0);
            $table->unsignedInteger('loans_today')->default(0);
            $table->unsignedInteger('returns_today')->default(0);
            $table->unsignedInteger('active_loans')->default(0);
            $table->unsignedInteger('overdue_loans')->default(0);
            $table->unsignedInteger('total_readers')->default(0);
            $table->unsignedInteger('active_readers')->default(0);
            $table->unsignedInteger('new_readers_today')->default(0);
            $table->decimal('total_fines', 10, 2)->default(0);
            $table->decimal('paid_fines', 10, 2)->default(0);
            $table->decimal('unpaid_fines', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_statistics');
    }
};
