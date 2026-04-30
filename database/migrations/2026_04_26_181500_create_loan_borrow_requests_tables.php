<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_borrow_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_code', 24)->unique();
            $table->foreignId('library_card_id')->constrained('library_cards')->restrictOnDelete();
            $table->unsignedInteger('requested_by')->comment('Bạn đọc tạo yêu cầu');
            $table->enum('loan_type', ['home', 'onsite'])->default('home')->index();
            $table->date('requested_loan_date')->nullable()->comment('Ngày mượn bạn đọc mong muốn');
            $table->date('requested_due_date')->nullable()->comment('Ngày hẹn trả bạn đọc mong muốn');
            $table->string('status', 20)->default('pending')->index()->comment('pending|approved|rejected|cancelled');
            $table->text('request_note')->nullable();
            $table->unsignedInteger('reviewed_by')->nullable()->comment('Thủ thư/Admin xử lý');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('approved_loan_id')->nullable()->constrained('loans')->nullOnDelete();
            $table->userAuditColumns();
            $table->timestamps();

            $table->index(['library_card_id', 'status']);
            $table->index(['requested_by', 'status']);
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('loan_borrow_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrow_request_id')->constrained('loan_borrow_requests')->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('books')->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('condition_on_loan', 20)->nullable()->comment('tot|hong|mat; thủ thư nhập khi duyệt');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['borrow_request_id', 'book_id'], 'loan_borrow_request_items_req_book_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_borrow_request_items');
        Schema::dropIfExists('loan_borrow_requests');
    }
};
