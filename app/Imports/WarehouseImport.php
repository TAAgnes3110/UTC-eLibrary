<?php

declare(strict_types=1);

namespace App\Imports;

use App\Helpers\FileHelpers;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;

final class WarehouseImport
{
    public const CODE_ALIASES = ['mã', 'ma', 'mã kho', 'ma kho', 'makho', 'ma_kho', 'Mã', 'code'];

    public const NAME_ALIASES = ['tên', 'ten', 'tên kho', 'ten kho', 'tenkho', 'ten_kho', 'Tên', 'name'];

    public static function import(UploadedFile $file): array
    {
        $success = 0;
        $skipped = 0;
        $errors = [];
        FileHelpers::readExcelInChunks($file, function (array $rows) use (&$success, &$skipped, &$errors): void {
            $upsertRows = [];
            foreach ($rows as $row) {
                try {
                    $code = FileHelpers::getValueByAliases($row, self::CODE_ALIASES);
                    $name = FileHelpers::getValueByAliases($row, self::NAME_ALIASES);
                    if (! $code || ! $name) {
                        $skipped++;
                        continue;
                    }

                    $upsertRows[] = [
                        'code' => $code,
                        'name' => $name,
                    ];
                    $success++;
                } catch (\Throwable $e) {
                    $errors[] = [
                        'row' => $row['_row_number'] ?? null,
                        'message' => $e->getMessage(),
                    ];
                }
            }

            if ($upsertRows !== []) {
                Warehouse::query()->upsert($upsertRows, ['code'], ['name']);
            }
        }, 1, 1000, 0);

        return FileHelpers::buildImportResult($success, $skipped, $errors);
    }
}
