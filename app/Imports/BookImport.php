<?php

declare(strict_types=1);

namespace App\Imports;

use App\Helpers\FileHelpers;
use App\Models\Author;
use App\Models\Book;
use App\Models\Classification;
use App\Models\ClassificationDetail;
use App\Models\Publisher;
use App\Models\Warehouse;
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

    public const CLASSIFICATION_DETAIL_CODE_ALIASES = [
        'mã phân loại chi tiết',
        'ma phan loai chi tiet',
        'phân loại sách chi tiết',
        'phan loai sach chi tiet',
        'classification_detail_code',
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
    public const CABINET_ALIASES = ['tủ', 'tu', 'tủ (cabinet)', 'tu (cabinet)', 'cabinet'];
    public const SHELF_ALIASES = ['kệ', 'ke', 'kệ (shelf)', 'ke (shelf)', 'shelf'];
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
                $classificationDetailCode = FileHelpers::getValueByAliases($row, self::CLASSIFICATION_DETAIL_CODE_ALIASES);
                $bookCode = FileHelpers::getValueByAliases($row, self::BOOK_CODE_ALIASES);
                $title = FileHelpers::getValueByAliases($row, self::TITLE_ALIASES);
                $authorsRaw = FileHelpers::getValueByAliases($row, self::AUTHORS_ALIASES);
                $publishersRaw = FileHelpers::getValueByAliases($row, self::PUBLISHERS_ALIASES);
                $warehouseCode = FileHelpers::getValueByAliases($row, self::WAREHOUSE_CODE_ALIASES);
                $classificationCode = FileHelpers::getValueByAliases($row, self::CLASSIFICATION_CODE_ALIASES);

                $quantity = (int) (FileHelpers::parseNumber(FileHelpers::getValueByAliases($row, self::QUANTITY_ALIASES)) ?? 0);

                $missing = [];
                if (!$title) {
                    $missing[] = 'Tên sách (*)';
                }
                if (!$warehouseCode) {
                    $missing[] = 'Kho sách (*)';
                }
                if ($quantity <= 0) {
                    $missing[] = 'Số lượng (*) (phải > 0)';
                }
                if (!empty($missing)) {
                    $errors[] = [
                        'row' => $row['_row_number'] ?? null,
                        'message' => 'Thiếu/không hợp lệ: ' . implode('; ', $missing) . '.',
                    ];
                    $skipped++;
                    continue;
                }

                $classificationDetail = null;
                if ($classificationDetailCode) {
                    $classificationDetail = ClassificationDetail::query()
                        ->where('code', $classificationDetailCode)
                        ->first();
                    if (!$classificationDetail) {
                        $errors[] = [
                            'row' => $row['_row_number'] ?? null,
                            'message' => "Phân loại sách chi tiết không tồn tại: \"{$classificationDetailCode}\".",
                        ];
                        $skipped++;
                        continue;
                    }
                }
                $classification = null;
                if (!$classificationDetail && $classificationCode) {
                    $classification = Classification::query()->where('code', $classificationCode)->first();
                    if (!$classification) {
                        $errors[] = [
                            'row' => $row['_row_number'] ?? null,
                            'message' => "Phân loại sách không tồn tại: \"{$classificationCode}\".",
                        ];
                        $skipped++;
                        continue;
                    }
                }

                $warehouse = Warehouse::query()->where('code', $warehouseCode)->first();
                if (!$warehouse) {
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
                $shelf = FileHelpers::getValueByAliases($row, self::SHELF_ALIASES);
                $summary = FileHelpers::getValueByAliases($row, self::SUMMARY_ALIASES);
                $notes = FileHelpers::getValueByAliases($row, self::NOTES_ALIASES);

                $book = null;
                if ($registrationNumber) {
                    $book = Book::query()->where('registration_number', $registrationNumber)->first();
                }
                if (!$book && $bookCode) {
                    $book = Book::query()->where('book_code', $bookCode)->first();
                }
                $registrationNumber = $registrationNumber ?: self::generateRegistrationNumber($warehouse);
                $bookCode = $bookCode ?: (($classificationDetail && $warehouse)
                    ? self::generateBookCode($classificationDetail, $warehouse)
                    : null);
                $payload = [
                    'registration_number' => $registrationNumber,
                    'book_code' => $bookCode,
                    'title' => $title,
                    'published_year' => $publishedYear,
                    'pages' => $pages !== null ? (int) $pages : null,
                    'book_size' => $bookSize,
                    'price' => $price !== null ? (int) $price : null,
                    'quantity' => $quantity,
                    'classification_detail_id' => $classificationDetail?->id,
                    'classification_id' => $classificationDetail?->classification_id ?? $classification?->id,
                    'warehouse_id' => $warehouse->id,
                    'cabinet' => $cabinet,
                    'shelf' => $shelf,
                    'summary' => $summary,
                    'notes' => $notes,
                ];

                DB::transaction(function () use (&$book, $payload, $authorsRaw, $publishersRaw) {
                    if ($book) {
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

    private static function generateRegistrationNumber(Warehouse $warehouse): string
    {
        $lastRegistration = Book::query()
            ->where('warehouse_id', $warehouse->id)
            ->whereNotNull('registration_number')
            ->orderByDesc('id')
            ->value('registration_number');

        $nextNumber = 1;
        if ($lastRegistration && preg_match('/(\d+)$/', $lastRegistration, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        }

        return sprintf('%s-%04d', $warehouse->code, $nextNumber);
    }

    private static function generateBookCode(ClassificationDetail $classificationDetail, Warehouse $warehouse): string
    {
        $shortClassificationCode = str_replace('.', '', (string) $classificationDetail->code);

        $lastBookCode = Book::query()
            ->where('classification_detail_id', $classificationDetail->id)
            ->where('warehouse_id', $warehouse->id)
            ->whereNotNull('book_code')
            ->orderByDesc('id')
            ->value('book_code');

        $nextOrder = 1;
        if ($lastBookCode && preg_match('/-(\d{4})$/', $lastBookCode, $matches)) {
            $nextOrder = (int) $matches[1] + 1;
        }

        $orderPart = str_pad((string) $nextOrder, 4, '0', STR_PAD_LEFT);
        return sprintf('%s-%s-%s', $shortClassificationCode, $warehouse->code, $orderPart);
    }

    private static function syncAuthors(Book $book, ?string $authorsRaw): void
    {
        $authorNames = self::splitNames($authorsRaw);
        if (!$authorNames) {
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
        if (!$publisherNames) {
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
}

