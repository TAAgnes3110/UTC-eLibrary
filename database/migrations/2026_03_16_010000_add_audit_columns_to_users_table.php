<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'created_by')) {
                $table->unsignedInteger('created_by')->nullable()->after('is_active');
                $table->foreign('created_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('users', 'updated_by')) {
                $table->unsignedInteger('updated_by')->nullable()->after('created_by');
                $table->foreign('updated_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('users', 'deleted_by')) {
                $table->unsignedInteger('deleted_by')->nullable()->after('updated_by');
                $table->foreign('deleted_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }

            if (Schema::hasColumn('users', 'updated_by')) {
                $table->dropForeign(['updated_by']);
                $table->dropColumn('updated_by');
            }

            if (Schema::hasColumn('users', 'deleted_by')) {
                $table->dropForeign(['deleted_by']);
                $table->dropColumn('deleted_by');
            }
        });
    }
};

