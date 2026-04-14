<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_renewal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->unsignedInteger('requested_by')->comment('Người gửi yêu cầu');
            $table->date('current_due_date')->comment('Hạn trả tại thời điểm gửi yêu cầu');
            $table->date('requested_due_date')->nullable()->comment('Hạn trả mong muốn sau gia hạn');
            $table->string('status', 20)->default('pending')->comment('pending|approved|rejected');
            $table->text('request_note')->nullable()->comment('Ghi chú của người dùng');
            $table->unsignedInteger('reviewed_by')->nullable()->comment('Người duyệt yêu cầu');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable()->comment('Ghi chú xử lý của admin/thủ thư');
            $table->timestamps();

            $table->index(['loan_id', 'status']);
            $table->index(['requested_by', 'status']);
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_renewal_requests');
    }
};
