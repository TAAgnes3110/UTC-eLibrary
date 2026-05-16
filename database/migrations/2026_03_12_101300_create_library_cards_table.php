<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->unsignedInteger('period_id')->nullable()->index();

            $table->string('card_number', 64)->index();

            $table->enum('holder_type', ['student', 'teacher', 'external'])
                ->default('external')
                ->index()
                ->comment('Sinh viên / Giảng viên / Bạn đọc ngoài');
            $table->string('code')->nullable()->index();
            $table->string('full_name', 150)->nullable();
            $table->string('email', 190)->nullable()->index();
            $table->string('phone', 20)->nullable()->index();
            $table->text('address')->nullable();

            $table->unsignedInteger('faculty_id')->nullable()->index();
            $table->unsignedInteger('department_id')->nullable()->index();
            $table->string('class_code', 80)->nullable()->comment('Mã lớp hành chính');

            $table->date('date_of_birth')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('external_organization', 150)->nullable();

            $table->string('workflow_status', 32)->default('draft')->index()->comment('draft|pending_payment|pending_review|…');
            $table->unsignedTinyInteger('status')->default(1)->index()->comment('LibraryCardStatus enum int');

            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();

            $table->unsignedInteger('issued_by')->nullable();
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('params')->nullable();
            $table->text('notes')->nullable()->comment('Ghi chú nội bộ / lý do từ chối');
            $table->timestamp('revoked_at')->nullable();
            $table->text('revoked_reason')->nullable();

            $table->userAuditColumns();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('period_id')->references('id')->on('periods')->nullOnDelete();
            $table->foreign('faculty_id')->references('id')->on('faculties')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('issued_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['holder_type', 'status'], 'library_cards_type_status_index');
            $table->index(['workflow_status', 'created_at', 'id'], 'library_cards_workflow_created_id_idx');
            $table->index(['holder_type', 'created_at', 'id'], 'library_cards_holder_created_id_idx');
            $table->index(['status', 'created_at', 'id'], 'library_cards_status_created_id_idx');
            $table->index(
                ['workflow_status', 'holder_type', 'status', 'created_at', 'id'],
                'library_cards_wf_holder_status_created_id_idx'
            );
            $table->index(['full_name', 'id'], 'library_cards_full_name_id_idx');
        });

        Schema::create('library_card_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_card_id')->unique()->constrained('library_cards')->cascadeOnDelete();

            $table->string('payment_status', 20)->nullable()->index()->comment('pending|paid|failed|refunded');
            $table->decimal('payment_amount', 12, 2)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method', 40)->nullable();
            $table->string('receipt_number', 50)->nullable();
            $table->unsignedInteger('payment_collected_by')->nullable();
            $table->foreign('payment_collected_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_card_payments');
        Schema::dropIfExists('library_cards');
    }
};
