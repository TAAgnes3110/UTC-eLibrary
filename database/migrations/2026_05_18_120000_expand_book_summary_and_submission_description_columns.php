<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->longText('summary')->nullable()->change();
            $table->longText('notes')->nullable()->change();
        });

        if (Schema::hasTable('digital_document_submissions')) {
            Schema::table('digital_document_submissions', function (Blueprint $table) {
                $table->longText('description')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->text('summary')->nullable()->change();
            $table->text('notes')->nullable()->change();
        });

        if (Schema::hasTable('digital_document_submissions')) {
            Schema::table('digital_document_submissions', function (Blueprint $table) {
                $table->text('description')->nullable()->change();
            });
        }
    }
};
