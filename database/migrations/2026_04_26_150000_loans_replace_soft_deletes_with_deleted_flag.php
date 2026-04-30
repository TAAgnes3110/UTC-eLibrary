<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->boolean('deleted')->default(false)->after('status')->comment('Ẩn phiếu khỏi danh sách khi xóa (chỉ áp dụng phiếu đã trả)');
        });

        if (Schema::hasColumn('loans', 'deleted_at')) {
            DB::table('loans')->whereNotNull('deleted_at')->update(['deleted' => true]);
            Schema::table('loans', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->softDeletes();
        });

        DB::table('loans')->where('deleted', true)->update(['deleted_at' => now()]);

        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('deleted');
        });
    }
};
