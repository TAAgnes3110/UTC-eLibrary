<?php

namespace App\Console\Commands;

use App\Services\LibraryCard\LibraryCardOverdueLockService;
use Illuminate\Console\Command;

/**
 * Đồng bộ trạng thái khóa thẻ theo phiếu quá hạn nặng (tách khỏi luồng tạo phiếu mượn).
 */
class SyncLibraryCardOverdueLocksCommand extends Command
{
    protected $signature = 'library-cards:sync-overdue-locks';

    protected $description = 'Khóa thẻ có phiếu quá hạn >30 ngày; mở khóa thẻ chỉ bị khóa tự động khi không còn phiếu như vậy';

    public function handle(LibraryCardOverdueLockService $service): int
    {
        $r = $service->syncLocksForSevereOverdue();
        $this->info(sprintf('Đã khóa thêm: %d thẻ. Đã mở khóa: %d thẻ.', $r['locked'], $r['unlocked']));

        return self::SUCCESS;
    }
}
