<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PeriodService;
use Illuminate\Console\Command;

final class SyncAdmissionPeriodsCommand extends Command
{
    protected $signature = 'periods:sync-admission';

    protected $description = 'Tự thêm niên khóa mới kể từ 1/8 năm tuyển (K = năm vào − 1959, chương trình 4 năm)';

    public function handle(PeriodService $service): int
    {
        $created = $service->syncDueCohorts();
        if ($created === 0) {
            $this->info('Không có niên khóa mới cần thêm (chưa tới 1/8 hoặc đã đủ dữ liệu).');
        } else {
            $this->info("Đã thêm {$created} niên khóa mới.");
        }

        return self::SUCCESS;
    }
}
