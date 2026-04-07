<?php

namespace App\Support\Database;

use App\Models\Traits\HasAuditFields;
use Illuminate\Database\Schema\Blueprint;

/**
 * Gom cột actor audit (FK → users) để migration gọn, thống nhất với {@see HasAuditFields}.
 */
final class MigrationBlueprintMacros
{
    public static function register(): void
    {
        Blueprint::macro('userAuditColumns', function (bool $withDeletedBy = true): void {
            /** @var Blueprint $this */
            $this->unsignedInteger('created_by')->nullable()->comment('Người tạo bản ghi');
            $this->unsignedInteger('updated_by')->nullable()->comment('Người cập nhật bản ghi');
            if ($withDeletedBy) {
                $this->unsignedInteger('deleted_by')->nullable()->comment('Người xóa (soft/hard)');
            }
            $this->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $this->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            if ($withDeletedBy) {
                $this->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
            }
        });
    }
}
