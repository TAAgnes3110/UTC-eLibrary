<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('books')
            ->whereIn('resource_type', ['thesis', 'journal'])
            ->update(['resource_type' => 'reference']);
    }

    public function down(): void
    {
        // Không rollback vì không thể xác định chính xác bản ghi thesis/journal ban đầu.
    }
};

