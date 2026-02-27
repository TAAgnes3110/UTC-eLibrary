<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add indexes for frequently queried columns (REST API & reporting).
     */
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->index('title');
            $table->index('status');
            $table->index('published_year');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->index('status');
            $table->index('due_date');
            $table->index('loan_date');
            $table->index(['user_id', 'status']);
        });

        Schema::table('book_copies', function (Blueprint $table) {
            $table->index('status');
            $table->index(['book_id', 'status']);
        });

        if (Schema::hasTable('reservations')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->index('status');
                $table->index(['user_id', 'status']);
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex(['title']);
            $table->dropIndex(['status']);
            $table->dropIndex(['published_year']);
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['due_date']);
            $table->dropIndex(['loan_date']);
            $table->dropIndex(['user_id', 'status']);
        });

        Schema::table('book_copies', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['book_id', 'status']);
        });

        if (Schema::hasTable('reservations')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropIndex(['status']);
                $table->dropIndex(['user_id', 'status']);
                $table->dropIndex(['created_at']);
            });
        }
    }
};
