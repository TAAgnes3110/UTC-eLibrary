<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Tối ưu list admin: lọc trạng thái + vai trò + sort theo id.
            $table->index(['is_active', 'user_type', 'id'], 'users_active_type_id_idx');

            // Tối ưu khi lọc vai trò độc lập (kèm sort id desc ở list).
            $table->index(['user_type', 'id'], 'users_type_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_active_type_id_idx');
            $table->dropIndex('users_type_id_idx');
        });
    }
};
