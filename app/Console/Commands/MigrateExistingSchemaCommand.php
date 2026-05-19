<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\QueryException;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * DB import từ backup SQL thường đã có bảng nhưng thiếu dòng trong `migrations`.
 * Lệnh này chạy từng migration pending; nếu MySQL báo bảng/cột/index đã tồn tại thì ghi nhận và tiếp tục.
 */
#[AsCommand(name: 'migrate:existing-schema', description: 'Migrate và bỏ qua schema đã có sẵn (DB import)')]
class MigrateExistingSchemaCommand extends Command
{
    protected $signature = 'migrate:existing-schema
                            {--force : Chạy khi APP_ENV=production}';

    protected $description = 'Chạy migration pending; bỏ qua lỗi bảng/cột đã tồn tại (DB restore/import)';

    public function handle(Migrator $migrator): int
    {
        if (! $this->option('force') && $this->laravel->environment('production')) {
            if (! $this->components->confirmToProceed('Chạy migrate trên production?', true)) {
                return self::FAILURE;
            }
        }

        $repository = $migrator->getRepository();

        if (! $repository->repositoryExists()) {
            $repository->createRepository();
        }

        $migrator->setOutput($this->output);

        $paths = $migrator->paths();
        $files = $migrator->getMigrationFiles($paths);
        $ran = $repository->getRan();
        $pending = array_values(array_diff(array_keys($files), $ran));

        if ($pending === []) {
            $this->components->info('Không có migration pending.');

            return self::SUCCESS;
        }

        $migrated = 0;
        $skipped = 0;

        foreach ($pending as $name) {
            try {
                $migrator->runPending([$name], ['pretend' => false]);

                if (in_array($name, $repository->getRan(), true)) {
                    $migrated++;
                } else {
                    $this->markMigrationAsRan($repository, $name);
                    $skipped++;
                }
            } catch (QueryException $e) {
                if (! $this->isSchemaAlreadyExists($e)) {
                    throw $e;
                }

                $this->markMigrationAsRan($repository, $name);
                $skipped++;
            }
        }

        $this->newLine();
        $this->components->info("Xong. Chạy mới: {$migrated}, bỏ qua (import): {$skipped}.");

        return self::SUCCESS;
    }

    protected function markMigrationAsRan($repository, string $name): void
    {
        if (in_array($name, $repository->getRan(), true)) {
            return;
        }

        $repository->log($name, $repository->getNextBatchNumber());
        $this->components->warn("Đã có schema — ghi nhận migration: {$name}");
    }

    protected function isSchemaAlreadyExists(QueryException $e): bool
    {
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        // MySQL: 1050 table exists, 1060 duplicate column, 1061 duplicate key name, 1091 can't drop (index đã đổi)
        if (in_array($driverCode, [1050, 1060, 1061, 1091], true)) {
            return true;
        }

        $sqlState = (string) ($e->errorInfo[0] ?? '');

        return in_array($sqlState, ['42S01', '42S21'], true);
    }
}
