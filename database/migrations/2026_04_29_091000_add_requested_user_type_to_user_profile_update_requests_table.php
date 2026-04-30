<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profile_update_requests', function (Blueprint $table) {
            $table->string('requested_user_type', 20)
                ->nullable()
                ->after('requested_code')
                ->comment('Loại bạn đọc yêu cầu xác nhận: STUDENT|TEACHER');
        });
    }

    public function down(): void
    {
        Schema::table('user_profile_update_requests', function (Blueprint $table) {
            $table->dropColumn('requested_user_type');
        });
    }
};
