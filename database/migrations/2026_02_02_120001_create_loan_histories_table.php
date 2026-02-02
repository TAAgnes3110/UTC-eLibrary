<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('loan_histories', function (Blueprint $table) {
      $table->increments('id');
      $table->unsignedInteger('loan_id');
      $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');

      $table->enum('action', [
        'created',
        'renewed',
        'returned',
        'overdue',
        'lost',
        'damaged',
        'cancelled'
      ])->comment('Hành động');

      $table->unsignedInteger('performed_by')->nullable();
      $table->foreign('performed_by')->references('id')->on('users')->onDelete('set null');

      $table->text('notes')->nullable();
      $table->json('metadata')->nullable()->comment('Dữ liệu bổ sung');

      $table->timestamp('performed_at')->useCurrent();

      $table->index(['loan_id', 'performed_at']);
      $table->index('action');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('loan_histories');
  }
};
