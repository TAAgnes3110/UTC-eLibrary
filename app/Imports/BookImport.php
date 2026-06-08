<?php

declare(strict_types=1);

namespace App\Imports;

use App\Enums\ResourceType;
use App\Helpers\FileHelpers;
use App\Models\Author;
use App\Models\Book;
use App\Models\Classification;
use App\Models\Publisher;
use App\Models\StorageCabinet;
use App\Models\Warehouse;
use App\Support\WarehouseBookIdentifiers;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

final class BookImport
{
    public const REGISTRATION_NUMBER_ALIASES = [
        'số đăng ký cá biệt',
        'so dk ca biet',
        'so_dk_ca_biet',
        'registration_number',
    ];

    public const BOOK_CODE_ALIASES = [
        'mã sách',
        'ma sach',
        'book_code',
    ];

    public const TITLE_ALIASES = [
        'nhan đề',
        'nhan de',
        'tên sách',
        'ten sach',
        'tên sách (*)',
        'ten sach (*)',
        'title',
    ];

    public const AUTHORS_ALIASES = [
        'tác giả',
        'tac gia',
        'tác giả (ngăn cách bằng dấu , hoặc ;)',
        'tac gia (ngan cach bang dau , hoac ;)',
        'authors',
    ];

    public const RESOURCE_TYPE_ALIASES = [
        'loại sách',
        'loai sach',
        'loại sách (0: giáo trình, 1: tham khảo)',
        'loai sach (0: giao trinh, 1: tham khao)',
        'loại sách (0: gt, 1: tk)',
        'loai sach (0: gt, 1: tk)',
        'loại tài liệu',
        'loai tai lieu',
        'resource_type',
    ];

    public const PUBLISHERS_ALIASES = [
        'nhà xuất bản',
        'nha xuat ban',
        'nhà xuất bản (ngăn cách bằng dấu , hoặc ;)',
        'nha xuat ban (ngan cach bang dau , hoac ;)',
        'publishers',
    ];

    public const CLASSIFICATION_CODE_ALIASES = [
        'mã phân loại',
        'ma phan loai',
        'phân loại sách',
        'phan loai sach',
        'phân loại sách (*)',
        'phan loai sach (*)',
        'classification_code',
    ];

    public const WAREHOUSE_CODE_ALIASES = [
        'mã kho',
        'ma kho',
        'kho sách',
        'kho sach',
        'kho sách (*)',
        'kho sach (*)',
        'warehouse_code',
    ];

    public const PUBLISHED_YEAR_ALIASES = ['năm xuất bản', 'nam xuat ban', 'published_year'];

    public const PAGES_ALIASES = ['số trang', 'so trang', 'pages'];

    public const BOOK_SIZE_ALIASES = ['khổ sách', 'book_size'];

    public const PRICE_ALIASES = ['giá tiền', 'gia tien', 'price'];

    public const QUANTITY_ALIASES = ['số lượng', 'so luong', 'số lượng (*)', 'so luong (*)', 'quantity'];

    public const CABINET_ALIASES = [
        'tủ',
        'tu',
        'tủ (cabinet)',
        'tu (cabinet)',
        'tủ sách',
        'tu sach',
        'tủ sách (tùy chọn)',
        'tu sach (tuy chon)',
        'cabinet',
    ];

    public const SUMMARY_ALIASES = ['tóm tắt', 'tom tat', 'summary'];

    public const NOTES_ALIASES = ['ghi chú', 'ghi chu', 'notes'];

    public static function import(UploadedFile $file): array
    {
        [$rows, $mainSheetIndex, $optionalSheets] = self::readWorkbookSheets($file);
        $classificationRows = $optionalSheets[$mainSheetIndex + 1] ?? [];
        $warehouseRows = $optionalSheets[$mainSheetIndex + 2] ?? [];
        $cabinetRows = $optionalSheets[$mainSheetIndex + 3] ?? [];

        $success = 0;
        $skipped = 0;
        $errors = [];
        $currentRowNumber = null;
        $autoWarehouse = null;

        try {
            DB::transaction(function () use (
                $rows,
                $classificationRows,
                $warehouseRows,
                $cabinetRows,
                &$success,
                &$skipped,
                &$errors,
                &$currentRowNumber,
                &$autoWarehouse
            ): void {
                self::syncClassificationsFromSheet($classificationRows);
                self::syncWarehousesFromSheet($warehouseRows);
                self::syncCabinetsFromSheet($cabinetRows);

                $classificationByCode = Classification::query()
                    ->get(['id', 'code', 'name'])
                    ->keyBy(static fn (Classification $item) => (string) $item->code);
                $warehouseByCode = Warehouse::query()
                    ->withTrashed()
                    ->get(['id', 'code', 'name', 'is_active', 'deleted_at'])
                    ->keyBy(static fn (Warehouse $item) => (string) $item->code);

                foreach ($rows as $row) {
                    $currentRowNumber = (int) ($row['_row_number'] ?? 0) ?: null;
                    $registrationNumber = FileHelpers::getValueByAliases($row, self::REGISTRATION_NUMBER_ALIASES);
                    $bookCodeFromExcel = FileHelpers::getValueByAliases($row, self::BOOK_CODE_ALIASES);
                    $title = FileHelpers::getValueByAliases($row, self::TITLE_ALIASES);
                    $authorsRaw = FileHelpers::getValueByAliases($row, self::AUTHORS_ALIASES);
                    $publishersRaw = FileHelpers::getValueByAliases($row, self::PUBLISHERS_ALIASES);
                    $resourceTypeRaw = FileHelpers::getValueByAliases($row, self::RESOURCE_TYPE_ALIASES);
                    $warehouseCode = FileHelpers::getValueByAliases($row, self::WAREHOUSE_CODE_ALIASES);
                    $classificationCode = self::normalizeClassificationCode(
                        FileHelpers::getValueByAliases($row, self::CLASSIFICATION_CODE_ALIASES)
                    );
                    $quantityRaw = FileHelpers::getValueByAliases($row, self::QUANTITY_ALIASES);

                    if (self::shouldSkipImportRow($title, $registrationNumber, $bookCodeFromExcel, $quantityRaw)) {
                        $skipped++;

                        continue;
                    }

                    $resourceType = self::normalizeResourceType($resourceTypeRaw);
                    if ($resourceType === ResourceType::DIGITAL->value) {
                        self::abortImport(
                            $errors,
                            $row['_row_number'] ?? null,
                            'Loại sách không hợp lệ: chỉ chấp nhận Giáo trình (0) hoặc Tham khảo (1).'
                        );
                    }

                    $quantity = (int) (FileHelpers::parseNumber($quantityRaw) ?? 0);
                    if ($quantity <= 0) {
                        self::abortImport(
                            $errors,
                            $row['_row_number'] ?? null,
                            'Thiếu/không hợp lệ: Số lượng (*) (phải > 0).'
                        );
                    }

                    $classification = null;
                    if ($classificationCode) {
                        $classification = $classificationByCode->get($classificationCode);
                        if (! $classification) {
                            $classification = Classification::query()->create([
                                'code' => $classificationCode,
                                'name' => 'Phân loại '.$classificationCode,
                            ]);
                            $classificationByCode->put($classificationCode, $classification);
                        }
                    }

                    $warehouse = self::resolveWarehouseForImport($warehouseCode, $autoWarehouse, $warehouseByCode);
                    if ($resourceType === null) {
                        $resourceType = self::inferResourceTypeFromWarehouse($warehouse)
                            ?? self::inferResourceTypeFromBookCode($bookCodeFromExcel);
                    }

                    $publishedYear = FileHelpers::parseYear(FileHelpers::getValueByAliases($row, self::PUBLISHED_YEAR_ALIASES));
                    $pages = FileHelpers::parseNumber(FileHelpers::getValueByAliases($row, self::PAGES_ALIASES));
                    $bookSize = FileHelpers::getValueByAliases($row, self::BOOK_SIZE_ALIASES);
                    $price = FileHelpers::parseNumber(FileHelpers::getValueByAliases($row, self::PRICE_ALIASES));
                    $cabinet = FileHelpers::getValueByAliases($row, self::CABINET_ALIASES);
                    $summary = FileHelpers::getValueByAliases($row, self::SUMMARY_ALIASES);
                    $notes = FileHelpers::getValueByAliases($row, self::NOTES_ALIASES);

                    $book = null;
                    if ($registrationNumber) {
                        $book = Book::query()->where('registration_number', $registrationNumber)->first();
                    }
                    if (! $book && ! $title) {
                        self::abortImport(
                            $errors,
                            $row['_row_number'] ?? null,
                            'Thiếu/không hợp lệ: Tên sách (*) (bắt buộc khi thêm mới).'
                        );
                    }
                    $registrationNumber = $registrationNumber ?: WarehouseBookIdentifiers::nextRegistrationNumber($warehouse);
                    $bookCode = $book?->book_code
                        ?: ($bookCodeFromExcel ?: WarehouseBookIdentifiers::nextBookCode($warehouse));
                    $payload = [
                        'registration_number' => $registrationNumber,
                        'book_code' => $bookCode,
                        'title' => $title,
                        'published_year' => $publishedYear,
                        'pages' => $pages !== null ? (int) $pages : null,
                        'book_size' => $bookSize,
                        'price' => $price !== null ? (int) $price : null,
                        'quantity' => $quantity,
                        'classification_id' => $classification?->id,
                        'warehouse_id' => $warehouse->id,
                        'cabinet' => $cabinet,
                        'summary' => $summary,
                        'notes' => $notes,
                    ];
                    if ($resourceType !== null) {
                        $payload['resource_type'] = $resourceType;
                    }

                    $storageError = self::ensureStorageLocationForPayload($payload, $warehouse, $classification);
                    if ($storageError !== null) {
                        self::abortImport($errors, $row['_row_number'] ?? null, $storageError);
                    }

                    if ($book) {
                        // Có mã sách rồi thì cập nhật số lượng (và metadata nếu được nhập).
                        $updatePayload = array_filter($payload, static fn ($v) => $v !== null);
                        $book->fill($updatePayload);
                        $book->save();
                    } else {
                        $book = Book::create($payload);
                    }

                    self::syncAuthors($book, $authorsRaw);
                    self::syncPublishers($book, $publishersRaw);

                    $success++;
                }
            });
        } catch (Throwable $e) {
            if ($e->getMessage() !== 'BOOK_IMPORT_ABORT') {
                $errors[] = [
                    'row' => $currentRowNumber,
                    'message' => $e->getMessage(),
                ];
            }

            $success = 0;
            $skipped = max(0, count($rows) - count($errors));
        }

        return FileHelpers::buildImportResult($success, $skipped, $errors);
    }

    /**
     * Đọc workbook một lần, trả về sheet sách chính và các sheet phụ.
     *
     * @return array{0: list<array<string,mixed>>, 1: int, 2: array<int, list<array<string,mixed>>>}
     */
    private static function readWorkbookSheets(UploadedFile $file): array
    {
        $filePath = $file->getRealPath() ?: '';
        if ($filePath === '' || ! is_file($filePath)) {
            return [[], 0, []];
        }

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $optionalSheets = [];
        $mainSheetIndex = 0;
        $mainRows = [];

        try {
            foreach ($spreadsheet->getAllSheets() as $index => $worksheet) {
                $rawData = $worksheet->toArray(null, true, true, true);
                $optionalSheets[$index] = self::parseGenericSheetRows($rawData, 1)['rows'];

                if ($mainRows !== []) {
                    continue;
                }

                $bookSheet = self::parseBookSheetRows($rawData);
                if ($bookSheet['rows'] !== []) {
                    $mainSheetIndex = $index;
                    $mainRows = $bookSheet['rows'];
                }
            }

            if ($mainRows === [] && isset($optionalSheets[0])) {
                $mainRows = $optionalSheets[0];
            }
        } finally {
            $spreadsheet->disconnectWorksheets();
        }

        return [$mainRows, $mainSheetIndex, $optionalSheets];
    }

    /**
     * @param  array<int, array<string, mixed>>  $data
     * @return array{headers: list<string>, rows: list<array<string, mixed>>}
     */
    private static function parseBookSheetRows(array $data): array
    {
        foreach ([1, 2] as $headerRow) {
            if (! isset($data[$headerRow])) {
                continue;
            }

            $headers = FileHelpers::normalizeHeaders($data[$headerRow]);
            $headerValues = array_values($headers);
            $hasTitle = in_array('tên sách (*)', $headerValues, true) || in_array('tên sách', $headerValues, true);
            $hasQuantity = in_array('số lượng (*)', $headerValues, true) || in_array('số lượng', $headerValues, true);
            if (! $hasTitle || ! $hasQuantity) {
                continue;
            }

            return [
                'headers' => $headerValues,
                'rows' => self::mapSheetDataRows($data, $headerRow, $headers),
            ];
        }

        return ['headers' => [], 'rows' => []];
    }

    /**
     * @param  array<int, array<string, mixed>>  $data
     * @return array{headers: list<string>, rows: list<array<string, mixed>>}
     */
    private static function parseGenericSheetRows(array $data, int $headerRow = 1): array
    {
        if (! isset($data[$headerRow])) {
            return ['headers' => [], 'rows' => []];
        }

        $headers = FileHelpers::normalizeHeaders($data[$headerRow]);

        return [
            'headers' => array_values($headers),
            'rows' => self::mapSheetDataRows($data, $headerRow, $headers),
        ];
    }

    /**
     * @param  array<string, string>  $headers
     * @return list<array<string, mixed>>
     */
    private static function mapSheetDataRows(array $data, int $headerRow, array $headers): array
    {
        $rows = [];
        foreach ($data as $rowIndex => $rowData) {
            if ($rowIndex <= $headerRow) {
                continue;
            }
            $values = array_values($rowData);
            if (FileHelpers::isEmptyRow($values)) {
                continue;
            }

            $mapped = [];
            foreach ($headers as $colLetter => $headerName) {
                $mapped[$headerName] = isset($rowData[$colLetter]) ? trim((string) $rowData[$colLetter]) : null;
            }
            $mapped['_row_number'] = (string) $rowIndex;
            $rows[] = $mapped;
        }

        return $rows;
    }

    private static function shouldSkipImportRow(
        ?string $title,
        ?string $registrationNumber,
        ?string $bookCodeFromExcel,
        ?string $quantityRaw
    ): bool {
        if ($title || $registrationNumber || $bookCodeFromExcel) {
            return false;
        }

        return $quantityRaw === null || trim($quantityRaw) === '';
    }

    private static function syncClassificationsFromSheet(array $rows): void
    {
        foreach ($rows as $row) {
            $code = trim((string) (FileHelpers::getValueByAliases($row, ['mã', 'ma', 'code']) ?? ''));
            $name = trim((string) (FileHelpers::getValueByAliases($row, ['tên', 'ten', 'name']) ?? ''));
            if ($code === '') {
                continue;
            }
            if ($name === '') {
                throw new \RuntimeException(sprintf(
                    'Sheet2_PhanLoaiSach dòng %s: thiếu tên phân loại cho mã "%s".',
                    (string) ($row['_row_number'] ?? '?'),
                    $code
                ));
            }

            Classification::query()->updateOrCreate(
                ['code' => $code],
                ['name' => $name]
            );
        }
    }

    private static function syncWarehousesFromSheet(array $rows): void
    {
        foreach ($rows as $row) {
            $code = trim((string) (FileHelpers::getValueByAliases($row, ['mã', 'ma', 'code']) ?? ''));
            $name = trim((string) (FileHelpers::getValueByAliases($row, ['tên', 'ten', 'name']) ?? ''));
            if ($code === '') {
                continue;
            }
            if ($name === '') {
                throw new \RuntimeException(sprintf(
                    'Sheet3_KhoSach dòng %s: thiếu tên kho cho mã "%s".',
                    (string) ($row['_row_number'] ?? '?'),
                    $code
                ));
            }

            $warehouse = Warehouse::query()->withTrashed()->where('code', $code)->first();
            if (! $warehouse) {
                Warehouse::query()->create([
                    'code' => $code,
                    'name' => $name,
                    'is_active' => true,
                ]);

                continue;
            }

            if ($warehouse->trashed()) {
                $warehouse->restore();
            }
            $warehouse->name = $name;
            $warehouse->is_active = true;
            $warehouse->save();
        }
    }

    private static function syncCabinetsFromSheet(array $rows): void
    {
        foreach ($rows as $row) {
            $cabinetCode = trim((string) (FileHelpers::getValueByAliases($row, ['mã tủ', 'ma tu', 'code']) ?? ''));
            $cabinetName = trim((string) (FileHelpers::getValueByAliases($row, ['tên tủ', 'ten tu', 'name']) ?? ''));
            $warehouseCode = trim((string) (FileHelpers::getValueByAliases($row, ['mã kho', 'ma kho', 'warehouse_code']) ?? ''));
            $classificationCode = trim((string) (FileHelpers::getValueByAliases($row, ['mã phân loại', 'ma phan loai', 'classification_code']) ?? ''));

            if ($cabinetCode === '' && $cabinetName === '' && $warehouseCode === '' && $classificationCode === '') {
                continue;
            }

            if ($warehouseCode === '' || $classificationCode === '') {
                throw new \RuntimeException(sprintf(
                    'Sheet4_TuSach dòng %s: cần đủ "Mã kho" và "Mã phân loại".',
                    (string) ($row['_row_number'] ?? '?')
                ));
            }

            $warehouse = Warehouse::query()->where('code', $warehouseCode)->first();
            $classification = Classification::query()->where('code', $classificationCode)->first();
            if (! $warehouse || ! $classification) {
                throw new \RuntimeException(sprintf(
                    'Sheet4_TuSach dòng %s: không tìm thấy kho "%s" hoặc phân loại "%s".',
                    (string) ($row['_row_number'] ?? '?'),
                    $warehouseCode,
                    $classificationCode
                ));
            }

            $resolvedCode = $cabinetCode !== '' ? $cabinetCode : self::generateStorageCabinetCode($warehouse);
            $resolvedName = $cabinetName !== ''
                ? $cabinetName
                : trim(sprintf('Tủ %s - %s', $classification->code, $classification->name));

            $cabinet = StorageCabinet::query()
                ->withTrashed()
                ->where('warehouse_id', $warehouse->id)
                ->where('classification_id', $classification->id)
                ->where('code', $resolvedCode)
                ->first();

            if (! $cabinet) {
                StorageCabinet::query()->create([
                    'warehouse_id' => (int) $warehouse->id,
                    'classification_id' => (int) $classification->id,
                    'code' => $resolvedCode,
                    'name' => mb_substr($resolvedName, 0, 160),
                    'is_active' => true,
                    'current_quantity' => 0,
                    'params' => [],
                ]);

                continue;
            }

            if ($cabinet->trashed()) {
                $cabinet->restore();
            }
            $cabinet->name = mb_substr($resolvedName, 0, 160);
            $cabinet->is_active = true;
            $cabinet->save();
        }
    }

    private static function abortImport(array &$errors, int|string|null $row, string $message): void
    {
        $errors[] = ['row' => $row, 'message' => $message];
        throw new \RuntimeException('BOOK_IMPORT_ABORT');
    }

    private static function resolveWarehouseForImport(
        ?string $warehouseCode,
        ?Warehouse &$autoWarehouse = null,
        ?Collection $warehouseByCode = null
    ): Warehouse {
        $warehouseCode = trim((string) $warehouseCode);
        if ($warehouseCode !== '') {
            $warehouse = $warehouseByCode?->get($warehouseCode);
            if ($warehouse instanceof Warehouse) {
                if ($warehouse->trashed()) {
                    $warehouse->restore();
                }
                if (! $warehouse->is_active) {
                    $warehouse->is_active = true;
                    $warehouse->save();
                }

                return $warehouse;
            }

            $created = Warehouse::query()->create([
                'code' => $warehouseCode,
                'name' => 'Kho '.$warehouseCode,
                'is_active' => true,
            ]);
            $warehouseByCode?->put($warehouseCode, $created);

            return $created;
        }

        if ($autoWarehouse instanceof Warehouse) {
            return $autoWarehouse;
        }

        $autoCode = self::nextAutoWarehouseCode();
        $autoWarehouse = Warehouse::query()->create([
            'code' => $autoCode,
            'name' => 'Kho nhập Excel '.$autoCode,
            'is_active' => true,
        ]);
        $warehouseByCode?->put($autoCode, $autoWarehouse);

        return $autoWarehouse;
    }

    private static function inferResourceTypeFromWarehouse(Warehouse $warehouse): ?string
    {
        $code = strtoupper(trim((string) $warehouse->code));
        $name = mb_strtolower((string) $warehouse->name);

        if (str_contains($code, 'GT') || str_contains($name, 'giáo trình') || str_contains($name, 'giao trinh')) {
            return ResourceType::TEXTBOOK->value;
        }
        if (str_contains($code, 'TK') || str_contains($name, 'tham khảo') || str_contains($name, 'tham khao')) {
            return ResourceType::REFERENCE->value;
        }

        return null;
    }

    private static function inferResourceTypeFromBookCode(?string $bookCode): ?string
    {
        $code = strtoupper(trim((string) $bookCode));
        if ($code === '') {
            return null;
        }
        if (str_starts_with($code, 'GT-') || str_starts_with($code, 'GT')) {
            return ResourceType::TEXTBOOK->value;
        }
        if (str_starts_with($code, 'TK-') || str_starts_with($code, 'TK')) {
            return ResourceType::REFERENCE->value;
        }

        return null;
    }

    private static function nextAutoWarehouseCode(): string
    {
        $prefix = 'KHO-IMP-';
        $max = 0;
        $codes = Warehouse::query()
            ->withTrashed()
            ->where('code', 'like', $prefix.'%')
            ->pluck('code');

        foreach ($codes as $code) {
            $suffix = substr((string) $code, strlen($prefix));
            if ($suffix !== '' && ctype_digit($suffix)) {
                $max = max($max, (int) $suffix);
            }
        }

        return $prefix.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    private static function syncAuthors(Book $book, ?string $authorsRaw): void
    {
        $authorNames = self::splitNames($authorsRaw);
        if (! $authorNames) {
            return;
        }
        $authorIds = [];
        $order = 0;
        foreach ($authorNames as $name) {
            $slug = Str::slug($name);
            $author = Author::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'params' => []]
            );
            $authorIds[$author->id] = ['order' => $order++];
        }
        if ($authorIds) {
            $book->authors()->syncWithoutDetaching($authorIds);
        }
    }

    private static function syncPublishers(Book $book, ?string $publishersRaw): void
    {
        $publisherNames = self::splitNames($publishersRaw);
        if (! $publisherNames) {
            return;
        }
        $publisherIds = [];
        $order = 0;
        foreach ($publisherNames as $name) {
            $slug = Str::slug($name);
            $publisher = Publisher::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'params' => []]
            );
            $publisherIds[$publisher->id] = ['order' => $order++];
        }
        if ($publisherIds) {
            $book->publishers()->syncWithoutDetaching($publisherIds);
        }
    }

    /** @return string[] */
    private static function splitNames(?string $raw): array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [];
        }
        // Support separators: "," or ";" (also fullwidth variants)
        $parts = preg_split('/[;,，；]+/u', $raw) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts), static fn ($v) => $v !== ''));

        return array_values(array_unique($parts));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function ensureStorageLocationForPayload(array &$payload, Warehouse $warehouse, ?Classification $classification): ?string
    {
        if (self::isDigitalDocumentWarehouse($warehouse)) {
            $payload['cabinet'] = null;

            return null;
        }

        if (! $classification) {
            return 'Thiếu/không hợp lệ: Phân loại sách (*) (bắt buộc để xác định tủ lưu trữ).';
        }

        $cabinet = StorageCabinet::withTrashed()
            ->where('warehouse_id', $warehouse->id)
            ->where('classification_id', $classification->id)
            ->orderByDesc('id')
            ->first();

        if (! $cabinet) {
            $cabinetName = trim(sprintf(
                'Tủ %s%s',
                $classification->code ? "{$classification->code} - " : '',
                (string) $classification->name
            ));
            $cabinet = StorageCabinet::query()->create([
                'warehouse_id' => (int) $warehouse->id,
                'classification_id' => (int) $classification->id,
                'code' => self::generateStorageCabinetCode($warehouse),
                'name' => mb_substr($cabinetName, 0, 160),
                'is_active' => true,
                'current_quantity' => 0,
                'params' => [],
            ]);
        } else {
            if ($cabinet->trashed()) {
                $cabinet->restore();
            }
            if (! $cabinet->is_active) {
                $cabinet->is_active = true;
                $cabinet->save();
            }
        }

        $existingCabinet = trim((string) ($payload['cabinet'] ?? ''));
        $payload['cabinet'] = $existingCabinet !== '' ? $existingCabinet : (string) $cabinet->name;

        return null;
    }

    private static function isDigitalDocumentWarehouse(Warehouse $warehouse): bool
    {
        $code = strtolower(trim((string) $warehouse->code));
        if (str_contains($code, 'kho-so')) {
            return true;
        }
        $name = strtolower((string) $warehouse->name);

        return str_contains($name, 'tài liệu số') || str_contains($name, 'tai lieu so');
    }

    private static function generateStorageCabinetCode(Warehouse $warehouse): string
    {
        $shortWarehouseCode = strtoupper(str_replace('KHO-', '', trim((string) $warehouse->code)));
        $prefix = 'TU-'.($shortWarehouseCode !== '' ? $shortWarehouseCode : 'WH').'-';

        $existingCodes = StorageCabinet::query()
            ->withTrashed()
            ->where('warehouse_id', $warehouse->id)
            ->pluck('code')
            ->filter()
            ->values();

        $max = 0;
        foreach ($existingCodes as $code) {
            $codeStr = (string) $code;
            if (! str_starts_with($codeStr, $prefix)) {
                continue;
            }
            $numberPart = substr($codeStr, strlen($prefix));
            if (! ctype_digit($numberPart)) {
                continue;
            }
            $max = max($max, (int) $numberPart);
        }

        return sprintf('%s%02d', $prefix, $max + 1);
    }

    /**
     * Hỗ trợ cả giá trị "MÃ - TÊN" từ dropdown Excel và giá trị chỉ "MÃ".
     */
    private static function normalizeClassificationCode(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        // Ưu tiên tách theo " - " để tránh cắt nhầm các mã có dấu "-" bên trong.
        if (str_contains($value, ' - ')) {
            $parts = explode(' - ', $value, 2);

            return trim((string) ($parts[0] ?? '')) ?: null;
        }

        return $value;
    }

    private static function normalizeResourceType(mixed $value): ?string
    {
        $raw = strtolower(trim((string) $value));
        if ($raw === '') {
            return null;
        }

        $mapped = match (true) {
            $raw === '0' => ResourceType::TEXTBOOK->value,
            $raw === '1' => ResourceType::REFERENCE->value,
            $raw === '2' => ResourceType::DIGITAL->value,
            in_array($raw, [ResourceType::TEXTBOOK->value, ResourceType::REFERENCE->value], true) => $raw,
            str_contains($raw, 'giao trinh') || str_contains($raw, 'giáo trình') => ResourceType::TEXTBOOK->value,
            str_contains($raw, 'tham khao') || str_contains($raw, 'tham khảo') => ResourceType::REFERENCE->value,
            str_contains($raw, 'luan van') || str_contains($raw, 'luận văn') || str_contains($raw, 'thesis') => ResourceType::REFERENCE->value,
            str_contains($raw, 'tap chi') || str_contains($raw, 'tạp chí') || str_contains($raw, 'journal') => ResourceType::REFERENCE->value,
            str_contains($raw, 'tai lieu so') || str_contains($raw, 'tài liệu số') || str_contains($raw, 'digital') => ResourceType::DIGITAL->value,
            default => null,
        };

        return $mapped;
    }
}
