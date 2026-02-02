<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedInteger('book_copy_id');
            $table->foreign('book_copy_id')->references('id')->on('book_copies')->onDelete('cascade');
            $table->unsignedInteger('librarian_id')->nullable();
            $table->foreign('librarian_id')->references('id')->on('users')->onDelete('set null');

            $table->date('loan_date')->comment('Ngày mượn');
            $table->date('due_date')->comment('Ngày hẹn trả');
            $table->date('return_date')->nullable()->comment('Ngày trả thực tế');

            $table->integer('overdue_days')->default(0)->comment('Số ngày quá hạn');
            $table->decimal('overdue_fine', 10, 2)->default(0)->comment('Tiền phạt quá hạn');

            $table->enum('status', ['active', 'returned', 'overdue', 'lost'])->default('active')->comment('Trạng thái');

            $table->enum('condition_on_loan', ['new', 'good', 'fair', 'poor'])->nullable()->comment('Tình trạng khi mượn');
            $table->enum('condition_on_return', ['new', 'good', 'fair', 'poor'])->nullable()->comment('Tình trạng khi trả');

            $table->integer('renewal_count')->default(0)->comment('Số lần gia hạn');
            $table->integer('max_renewals')->default(2)->comment('Số lần gia hạn tối đa');
            $table->date('last_renewal_date')->nullable()->comment('Ngày gia hạn cuối');

            $table->text('notes')->nullable()->comment('Ghi chú');
            $table->json('params')->nullable()->comment('Tham số bổ sung');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
