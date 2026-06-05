<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_items', function (Blueprint $table) {
            $table->unsignedTinyInteger('damage_percent')
                ->nullable()
                ->after('condition_on_return')
                ->comment('% mức hư hỏng khi trả (0–100); mất sách = 100');
        });
    }

    public function down(): void
    {
        Schema::table('loan_items', function (Blueprint $table) {
            $table->dropColumn('damage_percent');
        });
    }
};
