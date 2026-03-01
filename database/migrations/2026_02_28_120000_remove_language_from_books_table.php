<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('books', 'language')) {
            Schema::table('books', function (Blueprint $table) {
                $table->dropColumn('language');
            });
        }
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->string('language', 10)->nullable()->after('classification_detail');
        });
    }
};
