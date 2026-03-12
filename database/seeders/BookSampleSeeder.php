<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Classification;
use App\Models\ClassificationDetail;
use App\Models\Publisher;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BookSampleSeeder extends Seeder
{
    public function run(): void
    {
        // Phân loại mẫu cho sách chuyên ngành Giao thông vận tải tại UTC
        $class = Classification::firstOrCreate(
            ['code' => '624'],
            ['name' => 'Kết cấu, cầu đường bộ', 'params' => ['note' => 'Mã phân loại kỹ thuật cầu đường (mẫu).']]
        );

        $classDetail = ClassificationDetail::firstOrCreate(
            ['code' => '624.2'],
            ['name' => 'Cầu bê tông cốt thép', 'classification_id' => $class->id, 'params' => ['note' => 'Chi tiết cho cầu bê tông cốt thép.']]
        );

        // Kho sách mẫu - Thư viện Trung tâm UTC
        $warehouse = Warehouse::firstOrCreate(
            ['code' => 'TV-CHINH'],
            ['name' => 'Thư viện Trung tâm UTC', 'params' => ['campus' => 'Hà Nội']]
        );

        // Một số tác giả & NXB thường gặp trong sách chuyên ngành GTVT
        $authors = [
            'Nguyễn Viết Trung',
            'Đỗ Bá Lâm',
            'Phạm Hữu Vinh',
            'Trần Thị Thanh',
        ];

        $publishers = [
            'Giao thông Vận tải',
            'Xây dựng',
            'Khoa học và Kỹ thuật',
        ];

        $authorModels = [];
        foreach ($authors as $name) {
            $authorModels[$name] = Author::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'params' => []]
            );
        }

        $publisherModels = [];
        foreach ($publishers as $name) {
            $publisherModels[$name] = Publisher::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'params' => []]
            );
        }

        // Một vài đầu sách mẫu bám sát chuyên ngành của Trường ĐH Giao thông Vận tải
        $samples = [
            [
                'registration_number' => 'UTC0001',
                'book_code' => '624-UTC-0001',
                'title' => 'Cơ sở thiết kế đường ô tô',
                'sub_title' => null,
                'language' => 'Tiếng Việt',
                'edition' => null,
                'published_year' => 2018,
                'pages' => 350,
                'illustration_pages' => null,
                'book_size' => '19x27cm',
                'price' => 98000,
                'quantity' => 1,
                'summary' => 'Giáo trình cơ sở cho sinh viên ngành Kỹ thuật xây dựng công trình giao thông, trình bày các nguyên lý thiết kế đường ô tô.',
                'notes' => null,
                'series_name' => 'Giáo trình ĐH GTVT',
                'publisher_place' => 'Hà Nội',
                'cabinet' => 'GT1',
                'shelf' => 'A1',
                'classification' => $class,
                'detail' => $classDetail,
                'authors' => ['Nguyễn Viết Trung'],
                'publishers' => ['Giao thông Vận tải'],
            ],
            [
                'registration_number' => 'UTC0002',
                'book_code' => '624-UTC-0002',
                'title' => 'Tổ chức vận tải và dịch vụ logistics',
                'sub_title' => null,
                'language' => 'Tiếng Việt',
                'edition' => null,
                'published_year' => 2019,
                'pages' => 420,
                'illustration_pages' => null,
                'book_size' => '16x24cm',
                'price' => 120000,
                'quantity' => 1,
                'summary' => 'Tài liệu phục vụ các ngành Vận tải – Kinh tế, trình bày nguyên lý tổ chức vận tải và quản lý chuỗi cung ứng, logistics.',
                'notes' => null,
                'series_name' => null,
                'publisher_place' => 'Hà Nội',
                'cabinet' => 'VT1',
                'shelf' => 'B2',
                'classification' => $class,
                'detail' => $classDetail,
                'authors' => ['Đỗ Bá Lâm'],
                'publishers' => ['Giao thông Vận tải'],
            ],
            [
                'registration_number' => 'UTC0003',
                'book_code' => '624-UTC-0003',
                'title' => 'Kết cấu bê tông cốt thép – Cầu đường bộ',
                'sub_title' => null,
                'language' => 'Tiếng Việt',
                'edition' => null,
                'published_year' => 2017,
                'pages' => 290,
                'illustration_pages' => null,
                'book_size' => '19x27cm',
                'price' => 135000,
                'quantity' => 2,
                'summary' => 'Giáo trình chuyên sâu về thiết kế và kiểm toán kết cấu bê tông cốt thép trong công trình cầu đường bộ.',
                'notes' => null,
                'series_name' => null,
                'publisher_place' => 'Hà Nội',
                'cabinet' => 'CT1',
                'shelf' => 'C3',
                'classification' => $class,
                'detail' => $classDetail,
                'authors' => ['Phạm Hữu Vinh', 'Trần Thị Thanh'],
                'publishers' => ['Xây dựng'],
            ],
        ];

        foreach ($samples as $row) {
            $book = Book::firstOrCreate(
                ['registration_number' => $row['registration_number']],
                [
                    'book_code' => $row['book_code'],
                    'title' => $row['title'],
                    'sub_title' => $row['sub_title'],
                    'language' => $row['language'],
                    'edition' => $row['edition'],
                    'published_year' => $row['published_year'],
                    'pages' => $row['pages'],
                    'illustration_pages' => $row['illustration_pages'],
                    'book_size' => $row['book_size'],
                    'price' => $row['price'],
                    'quantity' => $row['quantity'],
                    'summary' => $row['summary'],
                    'notes' => $row['notes'],
                    'series_name' => $row['series_name'],
                    'publisher_place' => $row['publisher_place'],
                    'cabinet' => $row['cabinet'],
                    'shelf' => $row['shelf'],
                    'classification_id' => $row['classification']->id,
                    'classification_detail_id' => $row['detail']->id,
                    'warehouse_id' => $warehouse->id,
                    'params' => [],
                ]
            );

            // Gắn tác giả
            $authorIds = [];
            foreach ($row['authors'] as $name) {
                $a = $authorModels[$name] ?? Author::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name, 'params' => []]
                );
                $authorIds[$a->id] = ['order' => count($authorIds)];
            }
            if ($authorIds) {
                $book->authors()->syncWithoutDetaching($authorIds);
            }

            // Gắn nhà xuất bản
            $publisherIds = [];
            foreach ($row['publishers'] as $name) {
                $p = $publisherModels[$name] ?? Publisher::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name, 'params' => []]
                );
                $publisherIds[$p->id] = ['order' => count($publisherIds)];
            }
            if ($publisherIds) {
                $book->publishers()->syncWithoutDetaching($publisherIds);
            }
        }
    }
}

