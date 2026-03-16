<?php

namespace App\Imports;

use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class WarehouseImport implements ToCollection, WithStartRow, SkipsOnFailure, SkipsEmptyRows
{
    use SkipsFailures;

    private int $created = 0;
    private int $updated = 0;

    public function startRow(): int
    {
        return 3;
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
            $warehouse = Warehouse::query()->where('code', $code)->first();
            if ($warehouse) {
                if ($warehouse->name !== $name) {
                    $warehouse->name = $name;
                    $warehouse->save();
                }
                $this->updated++;
            } else {
                Warehouse::create([
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

