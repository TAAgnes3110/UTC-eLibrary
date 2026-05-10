<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('books')
            ->where('access_mode', 'onsite')
            ->update(['access_mode' => 'circulation_only']);
    }

    public function down(): void
    {
        DB::table('books')
            ->where('access_mode', 'circulation_only')
            ->update(['access_mode' => 'onsite']);
    }
};

