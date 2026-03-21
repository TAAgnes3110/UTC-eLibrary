<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('books', 'resource_kind')) {
            return;
        }
        DB::table('books')->whereNull('resource_kind')->update(['resource_kind' => 'print']);
        if (Schema::hasColumn('books', 'access_mode')) {
            DB::table('books')->whereNull('access_mode')->update(['access_mode' => 'circulation_only']);
        }
    }

    public function down(): void
    {
        //
    }
};
