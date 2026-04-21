<?php

declare(strict_types=1);

namespace App\Imports;

use App\Helpers\FileHelpers;
use App\Models\BookshelfCell;
use App\Models\Classification;
use App\Models\ClassificationDetail;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;

final class BookshelfCellImport
{
    private const MAX_ROWS = 20;
    private const MAX_COLUMNS = 20;
    public const WAREHOUSE_CODE_ALIASES = ['mã kho', 'ma kho', 'makho', 'warehouse_code', 'kho'];
    public const POSITION_ALIASES = ['vị trí', 'vi tri', 'position'];
    public const ROW_ALIASES = ['hàng', 'hang', 'row', 'row_index'];
    public const COLUMN_ALIASES = ['cột', 'cot', 'column', 'column_index'];
    public const LABEL_ALIASES = ['nhãn', 'nhan', 'label'];
    public const CLASSIFICATION_CODE_ALIASES = ['mã phân loại', 'ma phan loai', 'classification_code'];
    public const CLASSIFICATION_NAME_ALIASES = ['phân loại', 'phan loai', 'tên phân loại', 'ten phan loai', 'classification_name'];
    public const DETAIL_CODE_ALIASES = ['mã phân loại chi tiết', 'ma phan loai chi tiet', 'classification_detail_code'];
    public const DETAIL_NAME_ALIASES = ['phân loại chi tiết', 'phan loai chi tiet', 'tên phân loại chi tiết', 'ten phan loai chi tiet', 'classification_detail_name'];
    public const CURRENT_QTY_ALIASES = ['số lượng hiện tại', 'so luong hien tai', 'current_quantity'];
    public const MASTER_CODE_ALIASES = ['mã', 'ma', 'code'];
    public const MASTER_NAME_ALIASES = ['tên', 'ten', 'name'];
    public const DETAIL_CLASSIFICATION_CODE_ALIASES = ['mã phân loại chính', 'ma phan loai chinh', 'classification_code'];
    public const DETAIL_CODE_ALIASES_MASTER = ['mã phân loại chi tiết', 'ma phan loai chi tiet', 'code'];
    public const DETAIL_NAME_ALIASES_MASTER = ['tên', 'ten', 'name'];

    public static function import(UploadedFile $file): array
    {
        $success = 0;
        $skipped = 0;
        $errors = [];

        self::upsertWarehousesFromSheet($file);
        self::upsertClassificationsFromSheet($file);
        self::upsertClassificationDetailsFromSheet($file);
        $warehouseRows = Warehouse::query()->get(['id', 'code', 'name']);
        $warehouseMap = [];
        foreach ($warehouseRows as $warehouseRow) {
            $warehouseMap[(string) $warehouseRow->code] = (int) $warehouseRow->id;
            $warehouseMap[(string) $warehouseRow->name] = (int) $warehouseRow->id;
            $warehouseMap[self::normalizeDisplayValue(trim($warehouseRow->code.' - '.$warehouseRow->name, ' -'))] = (int) $warehouseRow->id;
        }
        $classificationRows = Classification::query()->get(['id', 'code', 'name']);
        $classificationMap = [];
        foreach ($classificationRows as $classificationRow) {
            $classificationMap[self::normalizeDisplayValue((string) $classificationRow->code)] = (int) $classificationRow->id;
            $classificationMap[self::normalizeDisplayValue((string) $classificationRow->name)] = (int) $classificationRow->id;
            $classificationMap[self::normalizeDisplayValue(trim($classificationRow->code.' - '.$classificationRow->name, ' -'))] = (int) $classificationRow->id;
        }
        $detailRows = ClassificationDetail::query()
            ->get(['id', 'classification_id', 'code', 'name']);
        $detailMap = [];
        foreach ($detailRows as $detailRow) {
            $detailMap[self::normalizeDisplayValue((string) $detailRow->code)] = [
                'id' => (int) $detailRow->id,
                'classification_id' => (int) $detailRow->classification_id,
            ];
            $detailMap[self::normalizeDisplayValue((string) $detailRow->name)] = [
                'id' => (int) $detailRow->id,
                'classification_id' => (int) $detailRow->classification_id,
            ];
            $detailMap[self::normalizeDisplayValue(trim($detailRow->code.' - '.$detailRow->name, ' -'))] = [
                'id' => (int) $detailRow->id,
                'classification_id' => (int) $detailRow->classification_id,
            ];
        }
        $occupiedByWarehouse = self::buildOccupiedPositions();

        FileHelpers::readExcelInChunks($file, function (array $rows) use (
            &$success,
            &$skipped,
            &$errors,
            $warehouseMap,
            $classificationMap,
            $detailMap,
            &$occupiedByWarehouse
        ): void {
            $upsertRows = [];

            foreach ($rows as $row) {
                try {
                    $warehouseCode = FileHelpers::getValueByAliases($row, self::WAREHOUSE_CODE_ALIASES);
                    $warehouseInput = self::normalizeDisplayValue((string) ($warehouseCode ?? ''));
                    $positionRaw = FileHelpers::getValueByAliases($row, self::POSITION_ALIASES);
                    $rowIndex = (int) (FileHelpers::getValueByAliases($row, self::ROW_ALIASES) ?? 0);
                    $columnIndex = (int) (FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES) ?? 0);
                    if (($rowIndex < 1 || $columnIndex < 1) && is_string($positionRaw) && $positionRaw !== '') {
                        [$parsedRow, $parsedColumn] = self::parsePosition($positionRaw);
                        if ($parsedRow > 0 && $parsedColumn > 0) {
                            $rowIndex = $parsedRow;
                            $columnIndex = $parsedColumn;
                        }
                    }

                    if (! $warehouseCode) {
                        $errors[] = [
                            'row' => $row['_row_number'] ?? null,
                            'message' => 'Thiếu thông tin bắt buộc: Kho sách.',
                        ];
                        continue;
                    }

                    $warehouseId = isset($warehouseMap[$warehouseInput]) ? (int) $warehouseMap[$warehouseInput] : 0;
                    if ($warehouseId < 1) {
                        $errors[] = [
                            'row' => $row['_row_number'] ?? null,
                            'message' => "Không tìm thấy kho với mã '{$warehouseCode}'.",
                        ];
                        continue;
                    }
                    if ($rowIndex < 1 || $columnIndex < 1) {
                        $errors[] = [
                            'row' => $row['_row_number'] ?? null,
                            'message' => 'Thiếu thông tin bắt buộc: Vị trí.',
                        ];
                        continue;
                    }
                    if ($rowIndex > self::MAX_ROWS || $columnIndex > self::MAX_COLUMNS) {
                        $errors[] = [
                            'row' => $row['_row_number'] ?? null,
                            'message' => 'Hàng/Cột vượt giới hạn 20x20.',
                        ];
                        continue;
                    }
                    $positionKey = $rowIndex.'-'.$columnIndex;
                    if (isset($occupiedByWarehouse[$warehouseId][$positionKey])) {
                        $errors[] = [
                            'row' => $row['_row_number'] ?? null,
                            'message' => "Vị trí R".str_pad((string) $rowIndex, 2, '0', STR_PAD_LEFT)."-C".str_pad((string) $columnIndex, 2, '0', STR_PAD_LEFT)." ở kho '{$warehouseCode}' đã được sử dụng.",
                        ];
                        continue;
                    }

                    $classificationCode = FileHelpers::getValueByAliases($row, self::CLASSIFICATION_CODE_ALIASES);
                    $classificationName = FileHelpers::getValueByAliases($row, self::CLASSIFICATION_NAME_ALIASES);
                    $classificationInput = self::normalizeDisplayValue((string) ($classificationCode ?: $classificationName ?: ''));
                    $detailCode = FileHelpers::getValueByAliases($row, self::DETAIL_CODE_ALIASES);
                    $detailName = FileHelpers::getValueByAliases($row, self::DETAIL_NAME_ALIASES);
                    $detailInput = self::normalizeDisplayValue((string) ($detailCode ?: $detailName ?: ''));
                    $classificationId = null;
                    $detailId = null;

                    if ($classificationInput) {
                        $classificationId = isset($classificationMap[$classificationInput]) ? (int) $classificationMap[$classificationInput] : null;
                        if (! $classificationId) {
                            $errors[] = [
                                'row' => $row['_row_number'] ?? null,
                                'message' => "Không tìm thấy phân loại '{$classificationInput}'.",
                            ];
                            continue;
                        }
                    }
                    if ($classificationId === null) {
                        $errors[] = [
                            'row' => $row['_row_number'] ?? null,
                            'message' => 'Thiếu thông tin bắt buộc: Phân loại.',
                        ];
                        continue;
                    }

                    if ($detailInput) {
                        $detailInfo = $detailMap[$detailInput] ?? null;
                        if (! is_array($detailInfo)) {
                            $errors[] = [
                                'row' => $row['_row_number'] ?? null,
                                'message' => "Không tìm thấy phân loại chi tiết '{$detailInput}'.",
                            ];
                            continue;
                        }
                        $detailId = (int) $detailInfo['id'];
                        $detailClassificationId = (int) $detailInfo['classification_id'];
                        if ($classificationId !== null && $classificationId !== $detailClassificationId) {
                            $errors[] = [
                                'row' => $row['_row_number'] ?? null,
                                'message' => 'Phân loại chi tiết không thuộc phân loại chính đã nhập.',
                            ];
                            continue;
                        }
                        if ($classificationId === null) {
                            $classificationId = $detailClassificationId;
                        }
                    }
                    if ($detailId === null) {
                        $errors[] = [
                            'row' => $row['_row_number'] ?? null,
                            'message' => 'Thiếu thông tin bắt buộc: Phân loại chi tiết.',
                        ];
                        continue;
                    }

                    $label = FileHelpers::getValueByAliases($row, self::LABEL_ALIASES);
                    $label = trim((string) ($label ?? ''));
                    if ($label === '') {
                        $errors[] = [
                            'row' => $row['_row_number'] ?? null,
                            'message' => 'Thiếu thông tin bắt buộc: Nhãn.',
                        ];
                        continue;
                    }

                    $currentQuantity = (int) (FileHelpers::getValueByAliases($row, self::CURRENT_QTY_ALIASES) ?? 0);
                    $upsertRows[] = [
                        'warehouse_id' => $warehouseId,
                        'row_index' => $rowIndex,
                        'column_index' => $columnIndex,
                        'label' => $label,
                        'classification_id' => $classificationId,
                        'classification_detail_id' => $detailId,
                        'current_quantity' => max(0, $currentQuantity),
                        'is_active' => true,
                    ];
                    $occupiedByWarehouse[$warehouseId][$positionKey] = true;
                    $success++;
                } catch (\Throwable $e) {
                    $errors[] = [
                        'row' => $row['_row_number'] ?? null,
                        'message' => $e->getMessage(),
                    ];
                }
            }

            if ($upsertRows !== []) {
                BookshelfCell::query()->upsert(
                    $upsertRows,
                    ['warehouse_id', 'row_index', 'column_index'],
                    ['label', 'classification_id', 'classification_detail_id', 'current_quantity', 'is_active']
                );
            }
        }, 1, 1000, 0);

        return FileHelpers::buildImportResult($success, $skipped, $errors);
    }

    private static function upsertWarehousesFromSheet(UploadedFile $file): void
    {
        $result = FileHelpers::readExcel($file, 1, 1);
        $rows = $result['rows'] ?? [];
        if (! is_array($rows) || $rows === []) {
            return;
        }

        $upsertRows = [];
        foreach ($rows as $row) {
            $code = FileHelpers::getValueByAliases($row, self::MASTER_CODE_ALIASES);
            $name = FileHelpers::getValueByAliases($row, self::MASTER_NAME_ALIASES);
            if (! $code || ! $name) {
                continue;
            }
            $upsertRows[] = ['code' => $code, 'name' => $name];
        }
        if ($upsertRows !== []) {
            Warehouse::query()->upsert($upsertRows, ['code'], ['name']);
        }
    }

    private static function upsertClassificationsFromSheet(UploadedFile $file): void
    {
        $result = FileHelpers::readExcel($file, 1, 2);
        $rows = $result['rows'] ?? [];
        if (! is_array($rows) || $rows === []) {
            return;
        }

        $upsertRows = [];
        foreach ($rows as $row) {
            $code = FileHelpers::getValueByAliases($row, self::MASTER_CODE_ALIASES);
            $name = FileHelpers::getValueByAliases($row, self::MASTER_NAME_ALIASES);
            if (! $code || ! $name) {
                continue;
            }
            $upsertRows[] = ['code' => $code, 'name' => $name];
        }
        if ($upsertRows !== []) {
            Classification::query()->upsert($upsertRows, ['code'], ['name']);
        }
    }

    private static function upsertClassificationDetailsFromSheet(UploadedFile $file): void
    {
        $result = FileHelpers::readExcel($file, 1, 3);
        $rows = $result['rows'] ?? [];
        if (! is_array($rows) || $rows === []) {
            return;
        }

        $classificationMap = Classification::query()->pluck('id', 'code')->all();
        $upsertRows = [];
        foreach ($rows as $row) {
            $classificationCode = FileHelpers::getValueByAliases($row, self::DETAIL_CLASSIFICATION_CODE_ALIASES);
            $detailCode = FileHelpers::getValueByAliases($row, self::DETAIL_CODE_ALIASES_MASTER);
            $name = FileHelpers::getValueByAliases($row, self::DETAIL_NAME_ALIASES_MASTER);
            if (! $classificationCode || ! $detailCode || ! $name) {
                continue;
            }
            $classificationId = isset($classificationMap[$classificationCode]) ? (int) $classificationMap[$classificationCode] : 0;
            if ($classificationId < 1) {
                continue;
            }
            $upsertRows[] = [
                'classification_id' => $classificationId,
                'code' => $detailCode,
                'name' => $name,
            ];
        }
        if ($upsertRows !== []) {
            ClassificationDetail::query()->upsert(
                $upsertRows,
                ['classification_id', 'code'],
                ['name']
            );
        }
    }

    /**
     * @return array<int,array<string,bool>>
     */
    private static function buildOccupiedPositions(): array
    {
        $occupied = [];
        $rows = BookshelfCell::query()
            ->get(['warehouse_id', 'row_index', 'column_index']);
        foreach ($rows as $row) {
            $warehouseId = (int) $row->warehouse_id;
            if ($warehouseId < 1) {
                continue;
            }
            $key = ((int) $row->row_index).'-'.((int) $row->column_index);
            $occupied[$warehouseId][$key] = true;
        }

        return $occupied;
    }

    /**
     * @param  array<int,array<string,bool>>  $occupiedByWarehouse
     * @return array{0:int,1:int}
     */
    private static function findFirstEmptySlot(int $warehouseId, array $occupiedByWarehouse): array
    {
        $occupied = $occupiedByWarehouse[$warehouseId] ?? [];
        for ($row = 1; $row <= self::MAX_ROWS; $row++) {
            for ($col = 1; $col <= self::MAX_COLUMNS; $col++) {
                $key = $row.'-'.$col;
                if (! isset($occupied[$key])) {
                    return [$row, $col];
                }
            }
        }

        return [0, 0];
    }

    /**
     * @return array{0:int,1:int}
     */
    private static function parsePosition(string $position): array
    {
        $normalized = strtoupper(trim($position));
        if ($normalized === '') {
            return [0, 0];
        }
        if (preg_match('/R\s*0*(\d{1,2})\s*-\s*C\s*0*(\d{1,2})/u', $normalized, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }
        if (preg_match('/^0*(\d{1,2})\s*-\s*0*(\d{1,2})$/u', $normalized, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }

        return [0, 0];
    }

    private static function normalizeDisplayValue(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        return preg_replace('/\s+/', ' ', $trimmed) ?: $trimmed;
    }
}
