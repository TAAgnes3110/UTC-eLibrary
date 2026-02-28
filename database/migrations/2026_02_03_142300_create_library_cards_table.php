<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('library_cards', function (Blueprint $table) {
      $table->increments('id');
      $table->string('card_number')->unique()->comment('Số thẻ');
      $table->unsignedInteger('user_id');
      $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

      $table->date('issue_date')->default(now())->comment('Ngày cấp');
      $table->date('expiry_date')->nullable()->comment('Hạn thẻ');

      $table->enum('status', ['active', 'locked', 'expired', 'lost'])->default('active')->comment('TT thẻ');
      $table->boolean('is_active')->default(true)->comment('Đang hoạt động');

      $table->string('card_type')->default('STANDARD')->comment('Loại thẻ');

      $table->text('note')->nullable()->comment('Ghi chú');
      $table->json('metadata')->nullable()->comment('Metadata');

      $table->timestamps();
      $table->softDeletes();

      $table->index('user_id');
      $table->index('status');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('library_cards');
  }
};
