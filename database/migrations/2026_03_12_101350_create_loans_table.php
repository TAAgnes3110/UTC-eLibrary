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
            $table->string('loan_code', 20)->unique();
            $table->foreignId('library_card_id')->constrained('library_cards')->restrictOnDelete();
            $table->enum('loan_type', ['home', 'onsite'])->default('home')->index();
            $table->date('loan_date')->comment('Ngày mượn');
            $table->date('due_date');
            $table->date('return_date')->nullable();
            $table->enum('status', ['da_muon', 'da_tra', 'qua_han'])->default('da_muon')->index();
            $table->userAuditColumns();

            $table->timestamps();

            $table->index(['library_card_id', 'status']);
            $table->index(['status', 'due_date'], 'loans_status_due_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
