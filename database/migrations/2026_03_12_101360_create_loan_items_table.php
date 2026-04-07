<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chi tiết phiếu mượn: một phiếu (loans) có nhiều dòng đầu sách (books).
     */
    public function up(): void
    {
        Schema::create('loan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')
                ->comment('Phiếu mượn (phieu_muon → loans)')
                ->constrained('loans')
                ->cascadeOnDelete();
            $table->foreignId('book_id')
                ->constrained('books')
                ->cascadeOnDelete();
            $table->unsignedInteger('quantity')->comment('Số lượng');
            $table->string('condition_on_loan', 100)->nullable()->comment('Tình trạng khi mượn');
            $table->string('condition_on_return', 100)->nullable()->comment('Tình trạng khi trả');
            $table->decimal('fine_amount', 10, 2)->default(0)->comment('Tiền phạt dòng');
            $table->text('notes')->nullable()->comment('Ghi chú');
            $table->json('params')->nullable()->comment('Mở rộng tùy nghiệp vụ (JSON)');
            $table->timestamps();

            $table->index(['loan_id', 'book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_items');
    }
};
