<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profile_update_requests', function (Blueprint $table) {
            $table->boolean('is_visible')
                ->default(true)
                ->after('status')
                ->index()
                ->comment('Hiển thị cho người gửi: true|false');
        });
    }

    public function down(): void
    {
        Schema::table('user_profile_update_requests', function (Blueprint $table) {
            $table->dropColumn('is_visible');
        });
    }
};
