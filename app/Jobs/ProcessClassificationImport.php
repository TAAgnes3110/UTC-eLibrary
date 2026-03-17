<?php

namespace App\Jobs;

use App\Enums\ImportStatus;
use App\Enums\ImportType;
use App\Helpers\FileHelpers;
use App\Models\Classification;
use App\Models\Import;
use App\Services\MasterDataService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessClassificationImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Import $import
    ) {
        $this->onQueue('imports');
    }

    public function handle(): void
    {
        $this->import->update([
            'status' => ImportStatus::PROCESSING,
            'started_at' => now(),
        ]);
        $filePath = storage_path('app/' . $this->import->file_path);
        $result = FileHelpers::readExcel($filePath);
        $rows = $result['rows'];
        $success = 0;
        $skipped = 0;
        $errors = [];
        $chunkSize = 1000;
        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            DB::transaction(function () use ($chunk, &$success, &$skipped, &$errors) {
                foreach ($chunk as $row) {
                    try {
                        $code = $row['mã phân loại'] ?? $row['ma phan loai'] ?? $row['code'] ?? null;
                        $name = $row['tên phân loại'] ?? $row['ten phan loai'] ?? $row['name'] ?? null;

                        if (!$code || !$name) {
                            $skipped++;
                            continue;
                        }

                        $classification = Classification::query()->where('code', $code)->first();
                        if ($classification) {
                            $classification->name = $name;
                            $classification->save();
                        } else {
                            Classification::create([
                                'code' => $code,
                                'name' => $name,
                            ]);
                        }
                        $success++;
                    } catch (\Throwable $e) {
                        $errors[] = [
                            'row' => $row['_row_number'] ?? null,
                            'message' => $e->getMessage(),
                        ];
                    }
                }
            });

            $this->import->update([
                'processed_rows' => $success + $skipped + count($errors),
                'success_rows' => $success,
                'skipped_rows' => $skipped,
                'error_rows' => count($errors),
            ]);
        }

        $status = empty($errors) ? ImportStatus::COMPLETED : ($success > 0 ? ImportStatus::PARTIAL : ImportStatus::FAILED);

        $this->import->update([
            'status' => $status,
            'finished_at' => now(),
            'meta' => [
                'errors' => array_slice($errors, 0, 50),
            ],
        ]);

        MasterDataService::clearCache();
    }
}

