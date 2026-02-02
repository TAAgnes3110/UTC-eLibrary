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

      // Thống kê sách
      $table->integer('total_books')->default(0);
      $table->integer('total_copies')->default(0);
      $table->integer('available_copies')->default(0);
      $table->integer('borrowed_copies')->default(0);

      // Thống kê mượn trả
      $table->integer('loans_today')->default(0);
      $table->integer('returns_today')->default(0);
      $table->integer('active_loans')->default(0);
      $table->integer('overdue_loans')->default(0);

      // Thống kê độc giả
      $table->integer('total_readers')->default(0);
      $table->integer('active_readers')->default(0);
      $table->integer('new_readers_today')->default(0);

      // Thống kê phạt
      $table->decimal('total_fines', 10, 2)->default(0);
      $table->decimal('paid_fines', 10, 2)->default(0);
      $table->decimal('unpaid_fines', 10, 2)->default(0);

      $table->timestamps();

      // Indexes
      $table->index('stat_date');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('library_statistics');
  }
};
