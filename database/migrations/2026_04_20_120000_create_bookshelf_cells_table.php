<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookshelf_cells', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->unsignedSmallInteger('row_index')->comment('Hàng trong ma trận, bắt đầu từ 1');
            $table->unsignedSmallInteger('column_index')->comment('Cột trong ma trận, bắt đầu từ 1');
            $table->string('label', 120)->nullable()->comment('Nhãn gợi nhớ: A01, B03, ...');
            $table->foreignId('classification_id')->nullable()->constrained('classifications')->nullOnDelete();
            $table->foreignId('classification_detail_id')->nullable()->constrained('classification_details')->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->json('params')->nullable();

            $table->userAuditColumns();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['warehouse_id', 'row_index', 'column_index'], 'bookshelf_cells_matrix_unique');
            $table->index(['warehouse_id', 'classification_id'], 'bookshelf_cells_wh_classification_idx');
            $table->index(['warehouse_id', 'classification_detail_id'], 'bookshelf_cells_wh_classification_detail_idx');
        });

        Schema::table('book_copies', function (Blueprint $table) {
            $table->foreignId('bookshelf_cell_id')
                ->nullable()
                ->after('warehouse_id')
                ->constrained('bookshelf_cells')
                ->nullOnDelete();
            $table->index(['warehouse_id', 'bookshelf_cell_id'], 'book_copies_warehouse_bookshelf_cell_idx');
        });
    }

    public function down(): void
    {
        Schema::table('book_copies', function (Blueprint $table) {
            $table->dropIndex('book_copies_warehouse_bookshelf_cell_idx');
            $table->dropConstrainedForeignId('bookshelf_cell_id');
        });

        Schema::dropIfExists('bookshelf_cells');
    }
};
