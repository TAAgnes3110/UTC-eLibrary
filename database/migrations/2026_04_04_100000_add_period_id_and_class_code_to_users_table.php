<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('period_id')->nullable()->after('cohort')->comment('Niên khóa (bảng periods)');
            $table->string('class_code', 100)->nullable()->after('period_id')->comment('Lớp hành chính');
            $table->foreign('period_id')->references('id')->on('periods')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['period_id']);
            $table->dropColumn(['period_id', 'class_code']);
        });
    }
};
