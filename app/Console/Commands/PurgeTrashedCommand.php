<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Department;
use App\Models\DigitalAsset;
use App\Models\Faculty;
use App\Models\Loan;
use App\Models\ThesisMetadata;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Console\Command;

final class PurgeTrashedCommand extends Command
{
    protected $signature = 'trash:purge {--days=30 : Số ngày giữ trong thùng rác}';

    protected $description = 'Force delete các bản ghi đã xóa mềm quá hạn';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        if ($days <= 0) {
            $days = 30;
        }

        $cutoff = Carbon::now()->subDays($days);
        $models = [
            User::class,
            Warehouse::class,
            DigitalAsset::class,
            ThesisMetadata::class,
            Book::class,
            BookCopy::class,
            Faculty::class,
            Department::class,
            Loan::class,
        ];

        $total = 0;
        foreach ($models as $modelClass) {
            $deleted = $modelClass::onlyTrashed()
                ->where('deleted_at', '<', $cutoff)
                ->forceDelete();
            $total += (int) $deleted;
            $this->line(sprintf('%s: %d', class_basename($modelClass), (int) $deleted));
        }

        $this->info(sprintf('Done. Total deleted: %d (older than %d days)', $total, $days));

        return self::SUCCESS;
    }
}
