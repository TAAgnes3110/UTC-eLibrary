<?php

namespace App\Console\Commands;

use App\Services\StorageQuantitySyncService;
use Illuminate\Console\Command;

class SyncStorageQuantitiesCommand extends Command
{
    protected $signature = 'storage:sync-quantities';

    protected $description = 'Đồng bộ current_quantity của tủ sách từ book_copies';

    public function handle(StorageQuantitySyncService $syncService): int
    {
        $syncService->syncAll();
        $this->info('Đã đồng bộ số lượng tủ sách thành công.');

        return self::SUCCESS;
    }
}
