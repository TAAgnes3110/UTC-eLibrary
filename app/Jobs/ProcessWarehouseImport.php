<?php

namespace App\Jobs;

use App\Enums\ImportStatus;
use App\Enums\ImportType;
use App\Helpers\FileHelpers;
use App\Models\Import;
use App\Models\Warehouse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessWarehouseImport implements ShouldQueue
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
        $total = count($rows);
        $success = 0;
        $skipped = 0;
        $errors = [];
        $chunkSize = 1000;
        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            DB::transaction(function () use ($chunk, &$success, &$skipped, &$errors) {
                foreach ($chunk as $row) {
                    try {
                        $code = $row['mã kho'] ?? $row['ma kho'] ?? $row['code'] ?? null;
                        $name = $row['tên kho'] ?? $row['ten kho'] ?? $row['name'] ?? null;
                        if (!$code || !$name) {
                            $skipped++;
                            continue;
                        }
                        $warehouse = Warehouse::query()->where('code', $code)->first();
                        if ($warehouse) {
                            $warehouse->name = $name;
                            $warehouse->save();
                        } else {
                            Warehouse::create([
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
    }
}

