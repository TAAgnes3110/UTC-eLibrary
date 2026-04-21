<?php

declare(strict_types=1);

namespace App\Exports;

use App\Helpers\FileHelpers;
use App\Models\Book;
use App\Models\Classification;
use App\Models\ClassificationDetail;
use App\Models\Warehouse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class BooksWorkbookExport
{
    public static function stream(?array $ids = null): StreamedResponse
    {
        $query = Book::query()
            ->with([
                'classification:id,code,name',
                'classificationDetail:id,code,name',
                'warehouse:id,code,name',
                'authors:id,name',
                'publishers:id,name',
            ]);

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        $books = $query->orderBy('id')->get();

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
                optional($book->classificationDetail)->code,
                optional($book->classificationDetail)->name,
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
                $book->shelf,
                $book->summary,
                $book->notes,
                $book->created_at?->toIso8601String(),
                $book->updated_at?->toIso8601String(),
                $book->deleted_at?->toIso8601String(),
            ];
        })->all();

        $classifications = Classification::query()->orderBy('code')->get(['code', 'name']);
        $classificationDetails = ClassificationDetail::query()
            ->with('classification:id,code')
            ->orderBy('classification_id')
            ->orderBy('code')
            ->get(['classification_id', 'code', 'name']);
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
                    'Mã phân loại chi tiết',
                    'Tên phân loại chi tiết',
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
                    'Kệ',
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
                'title' => 'Sheet3_PhanLoaiSachChiTiet',
                'headers' => ['Mã phân loại chính', 'Mã phân loại chi tiết', 'Tên'],
                'rows' => $classificationDetails->map(fn ($d) => [optional($d->classification)->code, $d->code, $d->name])->all(),
            ],
            [
                'title' => 'Sheet4_KhoSach',
                'headers' => ['Mã', 'Tên'],
                'rows' => $warehouses->map(fn ($w) => [$w->code, $w->name])->all(),
            ],
        ], 'FileSach.xlsx');
    }
}
