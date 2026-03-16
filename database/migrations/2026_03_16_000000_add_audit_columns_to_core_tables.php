<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'warehouses',
            'books',
            'loans',
            'authors',
            'publishers',
            'classifications',
            'classification_details',
            'book_copies',
            'loan_policies',
            'library_cards',
            'library_services',
            'posts',
            'cms_pages',
            'contact_messages',
            'book_authors',
            'book_publishers',
        ];

        foreach ($tables as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                if (!Schema::hasColumn($name, 'created_by')) {
                    $table->unsignedInteger('created_by')->nullable();
                    $table->foreign('created_by')
                        ->references('id')
                        ->on('users')
                        ->nullOnDelete();
                }

                if (!Schema::hasColumn($name, 'updated_by')) {
                    $table->unsignedInteger('updated_by')->nullable();
                    $table->foreign('updated_by')
                        ->references('id')
                        ->on('users')
                        ->nullOnDelete();
                }

                if (!Schema::hasColumn($name, 'deleted_by')) {
                    $table->unsignedInteger('deleted_by')->nullable();
                    $table->foreign('deleted_by')
                        ->references('id')
                        ->on('users')
                        ->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'warehouses',
            'books',
            'loans',
            'authors',
            'publishers',
            'classifications',
            'classification_details',
            'book_copies',
            'loan_policies',
            'library_cards',
            'library_services',
            'posts',
            'cms_pages',
            'contact_messages',
            'book_authors',
            'book_publishers',
        ];

        foreach ($tables as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                if (Schema::hasColumn($name, 'created_by')) {
                    $table->dropForeign([$name . '_created_by_foreign'] ?? 'created_by');
                    $table->dropColumn('created_by');
                }

                if (Schema::hasColumn($name, 'updated_by')) {
                    $table->dropForeign([$name . '_updated_by_foreign'] ?? 'updated_by');
                    $table->dropColumn('updated_by');
                }

                if (Schema::hasColumn($name, 'deleted_by')) {
                    $table->dropForeign([$name . '_deleted_by_foreign'] ?? 'deleted_by');
                    $table->dropColumn('deleted_by');
                }
            });
        }
    }
};

