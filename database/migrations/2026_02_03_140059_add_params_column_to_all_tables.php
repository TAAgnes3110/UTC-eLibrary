<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // List of tables that extend BaseModel and need params column
        $tables = [
            'authors',
            'books',
            'book_copies',
            'categories',
            'customers',
            'departments',
            'email_otps',
            'faculties',
            'fines',
            'library_settings',
            'loans',
            'loan_histories',
            'periods',
            'profiles',
            'publishers',
            'readers',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'params')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->json('params')->nullable()->comment('Additional parameters (JSON)');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'authors',
            'books',
            'book_copies',
            'categories',
            'customers',
            'departments',
            'email_otps',
            'faculties',
            'fines',
            'library_settings',
            'loans',
            'loan_histories',
            'periods',
            'profiles',
            'publishers',
            'readers',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'params')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('params');
                });
            }
        }
    }
};
