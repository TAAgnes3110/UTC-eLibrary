<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookshelf_cells', function (Blueprint $table): void {
            $table->unsignedInteger('current_quantity')
                ->default(0)
                ->after('label')
                ->comment('So luong sach hien tai tren ke');
        });
    }

    public function down(): void
    {
        Schema::table('bookshelf_cells', function (Blueprint $table): void {
            $table->dropColumn('current_quantity');
        });
    }
};
