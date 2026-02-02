<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fines', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('loan_id');
            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->decimal('amount', 10, 2)->comment('Số tiền phạt');
            $table->enum('reason', ['overdue', 'lost', 'damaged', 'other'])->comment('Lý do');
            $table->text('description')->nullable()->comment('Mô tả chi tiết');
            $table->enum('status', ['unpaid', 'paid', 'waived'])->default('unpaid')->comment('Trạng thái');
            $table->date('paid_date')->nullable()->comment('Ngày thanh toán');
            $table->string('payment_method')->nullable()->comment('Phương thức thanh toán');
            $table->unsignedInteger('processed_by')->nullable();
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
            $table->text('notes')->nullable()->comment('Ghi chú');
            $table->json('params')->nullable()->comment('Tham số bổ sung');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fines');
    }
};
