<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table): void {
            // Tối ưu list admin mặc định: lọc resource_type + deleted_at và sort created_at/id desc.
            $table->index(
                ['resource_type', 'deleted_at', 'created_at', 'id'],
                'books_resource_deleted_created_id_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table): void {
            $table->dropIndex('books_resource_deleted_created_id_idx');
        });
    }
};

