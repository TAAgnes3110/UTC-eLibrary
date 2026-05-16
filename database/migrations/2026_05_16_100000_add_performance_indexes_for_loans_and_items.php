<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->index('loan_date', 'loans_loan_date_index');
            $table->index('return_date', 'loans_return_date_index');
        });

        Schema::table('loan_items', function (Blueprint $table) {
            $table->index('book_id', 'loan_items_book_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex('loans_loan_date_index');
            $table->dropIndex('loans_return_date_index');
        });

        Schema::table('loan_items', function (Blueprint $table) {
            $table->dropIndex('loan_items_book_id_index');
        });
    }
};
