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
            $table->unsignedInteger('book_copy_id');
            $table->unsignedInteger('librarian_id')->nullable();
            $table->date('loan_date')->index();
            $table->date('due_date')->index();
            $table->date('return_date')->nullable()->index();
            $table->unsignedSmallInteger('overdue_days')->default(0);
            $table->decimal('overdue_fine', 10, 2)->default(0);
            $table->string('status', 20)->default('active')->index();
            $table->string('condition_on_loan', 20)->nullable();
            $table->string('condition_on_return', 20)->nullable();
            $table->unsignedTinyInteger('renewal_count')->default(0);
            $table->unsignedTinyInteger('max_renewals')->default(2);
            $table->date('last_renewal_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('params')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
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
