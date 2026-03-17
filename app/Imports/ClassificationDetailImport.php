<?php

namespace App\Imports;

use App\Models\Classification;
use App\Models\ClassificationDetail;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class ClassificationDetailImport implements ToCollection, WithStartRow, WithChunkReading, SkipsOnFailure, SkipsEmptyRows, ShouldQueue
{
    use SkipsFailures;

    private int $created = 0;
    private int $updated = 0;
    private int $skipped = 0;

    public function startRow(): int
    {
        return 3;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * Cấu trúc file: [0] = Mã phân loại chính, [1] = Mã chi tiết, [2] = Tên.
     *
     * @param Collection<int, array<int, mixed>> $rows
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $classificationCode = trim((string) ($row[0] ?? ''));
            $code = trim((string) ($row[1] ?? ''));
            $name = trim((string) ($row[2] ?? ''));

            if ($classificationCode === '' || $code === '' || $name === '') {
                continue;
            }

            $classification = Classification::query()->where('code', $classificationCode)->first();
            if (!$classification) {
                $this->skipped++;
                continue;
            }

            $detail = ClassificationDetail::query()->where('code', $code)->first();
            if ($detail) {
                $changed = false;
                if ($detail->name !== $name) {
                    $detail->name = $name;
                    $changed = true;
                }
                if ($detail->classification_id !== $classification->id) {
                    $detail->classification_id = $classification->id;
                    $changed = true;
                }
                if ($changed) {
                    $detail->save();
                }
                $this->updated++;
            } else {
                ClassificationDetail::create([
                    'classification_id' => $classification->id,
                    'code' => $code,
                    'name' => $name,
                ]);
                $this->created++;
            }
        }
    }

    public function getSummary(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
        ];
    }
}

