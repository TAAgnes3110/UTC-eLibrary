<?php

namespace App\Exports;

use App\Models\Book;
use App\Models\Classification;
use App\Models\ClassificationDetail;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class BookFileMauExport implements WithMultipleSheets
{
    public function __construct(
        protected ?array $bookIds = null,
    ) {
    }

    public function sheets(): array
    {
        return [
            'Sheet1_Sach' => $this->booksSheet(),
            'Sheet2_PhanLoaiSach' => $this->classificationsSheet(),
            'Sheet3_PhanLoaiSachChiTiet' => $this->classificationDetailsSheet(),
            'Sheet4_KhoSach' => $this->warehousesSheet(),
        ];
    }

    private function booksSheet(): SimpleTableExport
    {
        $query = Book::query()
            ->with([
                'classification:id,code,name',
                'classificationDetail:id,code,name',
                'warehouse:id,code,name',
                'authors:id,name',
                'publishers:id,name',
            ]);

        if (!empty($this->bookIds)) {
            $query->whereIn('id', $this->bookIds);
        }

        $rows = $query
            ->orderBy('id')
            ->get()
            ->map(function (Book $book) {
                return [
                    $book->registration_number,                        
                    optional($book->classificationDetail)->code,       
                    optional($book->classificationDetail)->name,       
                    $book->book_code,                                 
                    $book->title,                                     
                    $book->authors_label,                             
                    $book->publishers_label,                          
                    optional($book->warehouse)->name,                 
                    $book->published_year,                            
                    $book->language,                                  
                    $book->pages,                                     
                    $book->book_size,                                 
                    $book->price,                                     
                    $book->quantity,                                  
                ];
            });
        $headings = [
            'Số đăng ký cá biệt',
            'Phân loại sách',
            'Phân loại sách chi tiết',
            'Mã sách',
            'Nhan đề',
            'Tác giả',
            'Nhà xuất bản',
            'Kho sách',
            'Năm xuất bản',
            'Ngôn ngữ',
            'Số trang',
            'Khổ sách',
            'Giá tiền',
            'Số lượng',
        ];
        return new SimpleTableExport($rows, $headings);
    }

    private function classificationsSheet(): SimpleTableExport
    {
        $rows = Classification::query()
            ->orderBy('id')
            ->get()
            ->map(function (Classification $classification) {
                return [
                    $classification->code,
                    $classification->name,
                ];
            });

        $headings = [
            'Mã',
            'Tên',
        ];

        return new SimpleTableExport($rows, $headings);
    }

    private function classificationDetailsSheet(): SimpleTableExport
    {
        $rows = ClassificationDetail::query()
            ->orderBy('id')
            ->get()
            ->map(function (ClassificationDetail $detail) {
                return [
                    $detail->code,
                    $detail->name,
                ];
            });

        $headings = [
            'Mã',
            'Tên',
        ];

        return new SimpleTableExport($rows, $headings);
    }

    private function warehousesSheet(): SimpleTableExport
    {
        $rows = Warehouse::query()
            ->orderBy('id')
            ->get()
            ->map(function (Warehouse $warehouse) {
                return [
                    $warehouse->code,
                    $warehouse->name,
                ];
            });

        $headings = [
            'Mã',
            'Tên',
        ];
        return new SimpleTableExport($rows, $headings);
    }
}

