<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $statuses = ['active', 'returned', 'overdue', 'lost'];
        $conditions = ['new', 'good', 'fair', 'poor'];

        Schema::create('loans', function (Blueprint $table) use ($statuses, $conditions) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('book_copy_id');
            $table->unsignedInteger('librarian_id')->nullable();
            $table->date('loan_date');
            $table->date('due_date');
            $table->date('return_date')->nullable();
            $table->unsignedSmallInteger('overdue_days')->default(0);
            $table->decimal('overdue_fine', 10, 2)->default(0);
            $table->enum('status', $statuses)->default('active');
            $table->enum('condition_on_loan', $conditions)->nullable();
            $table->enum('condition_on_return', $conditions)->nullable();
            $table->unsignedTinyInteger('renewal_count')->default(0);
            $table->unsignedTinyInteger('max_renewals')->default(2);
            $table->date('last_renewal_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('params')->nullable()->comment('Tham số');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('due_date');
            $table->index('loan_date');
            $table->index('return_date');
            $table->index(['user_id', 'status']);
            $table->index('book_copy_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('book_copy_id')->references('id')->on('book_copies')->onDelete('cascade');
            $table->foreign('librarian_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
