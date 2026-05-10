<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MigrateImagesToMediaDiskCommand extends Command
{
    protected $signature = 'media:migrate-images
        {--from= : Source disk (default: config media.source_disk)}
        {--to= : Target disk (default: config media.target_disk)}
        {--only= : Chỉ chạy 1 bảng, ví dụ users}
        {--chunk=200 : Batch size}
        {--overwrite : Ghi đè file nếu đã tồn tại trên đích}
        {--dry-run : Chỉ thống kê, không ghi file/DB}';

    protected $description = 'Migrate ảnh từ local/public sang media disk (R2/S3/public).';

    public function handle(): int
    {
        $fromDisk = (string) ($this->option('from') ?: config('media.source_disk', 'public'));
        $toDisk = (string) ($this->option('to') ?: config('media.target_disk', config('filesystems.media_disk', 'public')));
        $targets = (array) config('media.image_migration_targets', []);
        $onlyTable = trim((string) $this->option('only'));
        $chunk = max(10, (int) $this->option('chunk'));
        $overwrite = (bool) $this->option('overwrite');
        $dryRun = (bool) $this->option('dry-run');

        if ($onlyTable !== '') {
            $targets = array_values(array_filter(
                $targets,
                static fn (array $t): bool => (string) ($t['table'] ?? '') === $onlyTable
            ));
        }

        if ($targets === []) {
            $this->warn('Không có target nào để migrate.');

            return self::SUCCESS;
        }

        if (! array_key_exists($fromDisk, config('filesystems.disks', []))) {
            $this->error("Source disk [{$fromDisk}] chưa được khai báo.");

            return self::FAILURE;
        }
        if (! array_key_exists($toDisk, config('filesystems.disks', []))) {
            $this->error("Target disk [{$toDisk}] chưa được khai báo.");

            return self::FAILURE;
        }

        $this->info("Media migrate từ [{$fromDisk}] -> [{$toDisk}]".($dryRun ? ' (dry-run)' : ''));

        $migrated = 0;
        $skipped = 0;
        $missing = 0;
        $failed = 0;

        foreach ($targets as $target) {
            $table = (string) ($target['table'] ?? '');
            $column = (string) ($target['column'] ?? '');
            if ($table === '' || $column === '') {
                continue;
            }

            $this->line("• {$table}.{$column}");

            DB::table($table)
                ->select(['id', $column])
                ->whereNotNull($column)
                ->orderBy('id')
                ->chunkById($chunk, function ($rows) use (
                    $table,
                    $column,
                    $fromDisk,
                    $toDisk,
                    $overwrite,
                    $dryRun,
                    &$migrated,
                    &$skipped,
                    &$missing,
                    &$failed
                ): void {
                    foreach ($rows as $row) {
                        $rawPath = trim((string) ($row->{$column} ?? ''));
                        $path = $this->normalizePath($rawPath);

                        if ($path === null) {
                            $skipped++;

                            continue;
                        }

                        if (! Storage::disk($fromDisk)->exists($path)) {
                            $missing++;

                            continue;
                        }

                        if (! $overwrite && Storage::disk($toDisk)->exists($path)) {
                            $skipped++;

                            continue;
                        }

                        if ($dryRun) {
                            $migrated++;

                            continue;
                        }

                        $read = Storage::disk($fromDisk)->readStream($path);
                        if ($read === false) {
                            $failed++;

                            continue;
                        }

                        $ok = Storage::disk($toDisk)->writeStream($path, $read, ['visibility' => 'public']);
                        if (is_resource($read)) {
                            fclose($read);
                        }
                        if (! $ok) {
                            $failed++;

                            continue;
                        }

                        // Giữ nguyên path DB, chỉ đổi nơi lưu thật sự.
                        DB::table($table)->where('id', $row->id)->update([$column => $path]);
                        $migrated++;
                    }
                });
        }

        $this->newLine();
        $this->info("Done. migrated={$migrated}, skipped={$skipped}, missing={$missing}, failed={$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function normalizePath(string $path): ?string
    {
        if ($path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return null;
        }

        $path = ltrim($path, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, 8);
        }

        return $path === '' ? null : $path;
    }
}
