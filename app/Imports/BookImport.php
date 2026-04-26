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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        'loại sách (0: giáo trình, 1: tham khảo, 2: tài liệu số)',
        'loai sach (0: giao trinh, 1: tham khao, 2: tai lieu so)',
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

    public const BOOK_SIZE_ALIASES = ['khổ sách', 'kho sach', 'book_size'];

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
        $result = FileHelpers::readExcel($file, 1, 0);
        $rows = $result['rows'];

        $success = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $row) {
            try {
                $registrationNumber = FileHelpers::getValueByAliases($row, self::REGISTRATION_NUMBER_ALIASES);
                $title = FileHelpers::getValueByAliases($row, self::TITLE_ALIASES);
                $authorsRaw = FileHelpers::getValueByAliases($row, self::AUTHORS_ALIASES);
                $publishersRaw = FileHelpers::getValueByAliases($row, self::PUBLISHERS_ALIASES);
                $resourceType = self::normalizeResourceType(
                    FileHelpers::getValueByAliases($row, self::RESOURCE_TYPE_ALIASES)
                );
                $warehouseCode = FileHelpers::getValueByAliases($row, self::WAREHOUSE_CODE_ALIASES);
                $classificationCode = self::normalizeClassificationCode(
                    FileHelpers::getValueByAliases($row, self::CLASSIFICATION_CODE_ALIASES)
                );

                $quantity = (int) (FileHelpers::parseNumber(FileHelpers::getValueByAliases($row, self::QUANTITY_ALIASES)) ?? 0);

                $missing = [];
                if (! $warehouseCode) {
                    $missing[] = 'Kho sách (*)';
                }
                if ($quantity <= 0) {
                    $missing[] = 'Số lượng (*) (phải > 0)';
                }
                if (! empty($missing)) {
                    $errors[] = [
                        'row' => $row['_row_number'] ?? null,
                        'message' => 'Thiếu/không hợp lệ: '.implode('; ', $missing).'.',
                    ];
                    $skipped++;

                    continue;
                }

                $classification = null;
                if ($classificationCode) {
                    $classification = Classification::query()->where('code', $classificationCode)->first();
                    if (! $classification) {
                        $errors[] = [
                            'row' => $row['_row_number'] ?? null,
                            'message' => "Phân loại sách không tồn tại: \"{$classificationCode}\".",
                        ];
                        $skipped++;

                        continue;
                    }
                }

                $warehouse = Warehouse::query()->where('code', $warehouseCode)->first();
                if (! $warehouse) {
                    $errors[] = [
                        'row' => $row['_row_number'] ?? null,
                        'message' => "Kho sách không tồn tại: \"{$warehouseCode}\".",
                    ];
                    $skipped++;

                    continue;
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
                    $errors[] = [
                        'row' => $row['_row_number'] ?? null,
                        'message' => 'Thiếu/không hợp lệ: Tên sách (*) (bắt buộc khi thêm mới).',
                    ];
                    $skipped++;

                    continue;
                }
                $registrationNumber = $registrationNumber ?: WarehouseBookIdentifiers::nextRegistrationNumber($warehouse);
                $bookCode = $book?->book_code ?: WarehouseBookIdentifiers::nextBookCode($warehouse);
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
                    $errors[] = [
                        'row' => $row['_row_number'] ?? null,
                        'message' => $storageError,
                    ];
                    $skipped++;

                    continue;
                }

                DB::transaction(function () use (&$book, $payload, $authorsRaw, $publishersRaw) {
                    if ($book) {
                        // Có mã sách rồi thì cập nhật số lượng (và metadata nếu được nhập).
                        $book->fill($payload);
                        $book->save();
                    } else {
                        $book = Book::create($payload);
                    }

                    self::syncAuthors($book, $authorsRaw);
                    self::syncPublishers($book, $publishersRaw);
                });

                $success++;
            } catch (\Throwable $e) {
                $errors[] = [
                    'row' => $row['_row_number'] ?? null,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return FileHelpers::buildImportResult($success, $skipped, $errors);
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
            in_array($raw, ResourceType::values(), true) => $raw,
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
