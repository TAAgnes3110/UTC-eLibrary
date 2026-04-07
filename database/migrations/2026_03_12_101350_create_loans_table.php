<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_card_id')
                ->comment('Thẻ bạn đọc; user nội bộ qua library_cards.user_id')
                ->constrained('library_cards')
                ->restrictOnDelete();
            $table->foreignId('book_copy_id')
                ->comment('Bản sách mượn')
                ->constrained('book_copies')
                ->cascadeOnDelete();
            $table->unsignedInteger('user_id')->comment('Thủ thư / người lập phiếu mượn');

            $table->date('loan_date')->comment('Ngày mượn');
            $table->date('due_date')->comment('Ngày hẹn trả');
            $table->date('return_date')->nullable()->comment('Ngày trả thực tế');

            $table->enum('status', ['dang_muon', 'da_tra', 'qua_han'])
                ->default('dang_muon')
                ->index()
                ->comment('dang_muon=đang mượn, da_tra=đã trả, qua_han=quá hạn');

            $table->text('notes')->nullable()->comment('Ghi chú');
            $table->json('params')->nullable()->comment('Mở rộng tùy nghiệp vụ (JSON)');

            $table->timestamps();

            $table->index(['library_card_id', 'status']);
            $table->index(['status', 'due_date'], 'loans_status_due_date_index');

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
