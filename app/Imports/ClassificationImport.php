<?php

namespace App\Imports;

use App\Models\Classification;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class ClassificationImport implements ToCollection, WithStartRow, WithChunkReading, SkipsOnFailure, SkipsEmptyRows, ShouldQueue
{
    use SkipsFailures;

    private int $created = 0;
    private int $updated = 0;

    public function startRow(): int
    {
        return 3;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * @param Collection<int, array<int, mixed>> $rows
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $code = trim((string) ($row[0] ?? ''));
            $name = trim((string) ($row[1] ?? ''));

            if ($code === '' || $name === '') {
                continue;
            }

            $classification = Classification::query()->where('code', $code)->first();
            if ($classification) {
                if ($classification->name !== $name) {
                    $classification->name = $name;
                    $classification->save();
                }
                $this->updated++;
            } else {
                Classification::create([
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
        ];
    }
}

