<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\ResourceType;
use App\Helpers\FileHelpers;
use App\Models\Book;
use App\Models\Classification;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class BooksWorkbookExport
{
    public static function stream(?array $ids = null, ?string $resourceType = null): StreamedResponse
    {
        $query = Book::query()
            ->with([
                'classification:id,code,name',
                'warehouse:id,code,name',
                'authors:id,name',
                'publishers:id,name',
                'digitalAssets',
            ]);

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }
        if ($resourceType !== null && $resourceType !== '') {
            $query->where('resource_type', $resourceType);
        }

        $books = $query->orderBy('id')->get();
        $isDigital = $resourceType === ResourceType::DIGITAL->value;

        if ($isDigital) {
            $rows = $books->map(function (Book $book) {
                $asset = $book->digitalAssets
                    ->sortByDesc(fn ($it) => (int) ($it->is_primary ?? false))
                    ->first();
                $assetPath = $asset?->path;
                $assetUrl = null;
                if (! empty($assetPath)) {
                    $disk = $asset?->storage_disk ?: 'public';
                    /** @var \Illuminate\Filesystem\FilesystemAdapter $assetStorage */
                    $assetStorage = Storage::disk($disk);
                    $assetUrl = $assetStorage->exists($assetPath)
                        ? $assetStorage->url($assetPath)
                        : null;
                }

                $coverUrl = null;
                if (! empty($book->cover_image) && ! str_starts_with((string) $book->cover_image, 'http')) {
                    /** @var \Illuminate\Filesystem\FilesystemAdapter $mediaStorage */
                    $mediaStorage = Storage::disk((string) config('filesystems.media_disk', 'public'));
                    $coverUrl = $mediaStorage->url((string) $book->cover_image);
                } else {
                    $coverUrl = $book->cover_image;
                }

                return [
                    $book->book_code,
                    $book->title,
                    $book->authors_label,
                    $book->summary,
                    $asset?->original_name,
                    $assetUrl,
                    $coverUrl,
                    $book->created_at?->toIso8601String(),
                    $book->updated_at?->toIso8601String(),
                ];
            })->all();

            return FileHelpers::downloadWorkbook([
                [
                    'title' => 'TaiLieuSo',
                    'headers' => [
                        'Mã sách',
                        'Tên sách',
                        'Tác giả',
                        'Mô tả',
                        'Tên file đính kèm',
                        'Link file đính kèm',
                        'Link ảnh bìa',
                        'Created at',
                        'Updated at',
                    ],
                    'rows' => $rows,
                ],
            ], 'FileTaiLieuSo.xlsx');
        }

        $sheet1Rows = $books->map(function (Book $book) {
            return [
                $book->id,
                $book->registration_number,
                $book->book_code,
                $book->title,
                $book->sub_title,
                $book->authors_label,
                $book->publishers_label,
                optional($book->classification)->code,
                optional($book->classification)->name,
                optional($book->warehouse)->code,
                optional($book->warehouse)->name,
                $book->published_year,
                $book->language,
                $book->edition,
                $book->pages,
                $book->illustration_pages,
                $book->book_size,
                $book->price,
                $book->quantity,
                $book->publisher_place,
                $book->cabinet,
                $book->summary,
                $book->notes,
                $book->created_at?->toIso8601String(),
                $book->updated_at?->toIso8601String(),
                $book->deleted_at?->toIso8601String(),
            ];
        })->all();

        $classifications = Classification::query()->roots()->orderBy('code')->get(['code', 'name']);
        $warehouses = Warehouse::query()->orderBy('code')->get(['code', 'name']);

        return FileHelpers::downloadWorkbook([
            [
                'title' => 'Sheet1_Sach',
                'headers' => [
                    'ID',
                    'Số đăng ký cá biệt',
                    'Mã sách',
                    'Nhan đề',
                    'Nhan đề phụ',
                    'Tác giả',
                    'Nhà xuất bản',
                    'Mã phân loại',
                    'Tên phân loại',
                    'Mã kho',
                    'Tên kho',
                    'Năm xuất bản',
                    'Ngôn ngữ',
                    'Lần xuất bản',
                    'Số trang',
                    'Số trang minh họa',
                    'Khổ sách',
                    'Giá tiền',
                    'Số lượng',
                    'Nơi xuất bản',
                    'Tủ',
                    'Tóm tắt',
                    'Ghi chú',
                    'Created at',
                    'Updated at',
                    'Deleted at',
                ],
                'rows' => $sheet1Rows,
            ],
            [
                'title' => 'Sheet2_PhanLoaiSach',
                'headers' => ['Mã', 'Tên'],
                'rows' => $classifications->map(fn ($c) => [$c->code, $c->name])->all(),
            ],
            [
                'title' => 'Sheet3_KhoSach',
                'headers' => ['Mã', 'Tên'],
                'rows' => $warehouses->map(fn ($w) => [$w->code, $w->name])->all(),
            ],
        ], 'FileSach.xlsx');
    }
}
