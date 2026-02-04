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
      $table->string('card_number')->unique()->comment('Số thẻ thư viện');
      $table->unsignedInteger('user_id');
      $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

      $table->date('issue_date')->default(now())->comment('Ngày cấp');
      $table->date('expiry_date')->nullable()->comment('Ngày hết hạn');

      $table->enum('status', ['active', 'locked', 'expired', 'lost'])->default('active')->comment('Trạng thái thẻ');
      $table->boolean('is_active')->default(true)->comment('Có đang hoạt động không');

      $table->string('card_type')->default('STANDARD')->comment('Loại thẻ (VIP, Normal, Student...)');

      $table->text('note')->nullable()->comment('Ghi chú');
      $table->json('metadata')->nullable()->comment('Thông tin bổ sung');

      $table->timestamps();
      $table->softDeletes();
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
