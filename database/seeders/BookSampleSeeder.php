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
        // Phân loại chính 5 (Toán) + chi tiết 51 (Toán tiểu học)
        $class = Classification::firstOrCreate(
            ['code' => '5'],
            ['name' => 'Toán học tiểu học', 'params' => []]
        );

        $classDetail = ClassificationDetail::firstOrCreate(
            ['code' => '51'],
            ['name' => 'Toán lớp 1', 'classification_id' => $class->id, 'params' => []]
        );

        // Kho sách mẫu (từ file mẫu: cột Kho sách thường là mã 083 - ở đây dùng CNTT giả lập)
        $warehouse = Warehouse::firstOrCreate(
            ['code' => 'CNTT'],
            ['name' => 'Kho sách Công nghệ thông tin', 'params' => []]
        );

        // Một số tác giả và NXB xuất hiện trong file CSV
        $authors = [
            'Đỗ Tiến Đạt',
            'Huỳnh Châu',
            'Phạm Đình Thực',
        ];

        $publishers = [
            'Giáo dục',
            'Đại học Sư phạm',
            'Tổng Hợp',
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

        // Tạo một vài sách mẫu dựa trên các dòng đầu trong CSV
        $samples = [
            [
                'registration_number' => 'DKCB0001',
                'book_code' => '5-51-0001',
                'title' => '100 trò chơi học toán lớp 1',
                'sub_title' => null,
                'language' => 'Tiếng Việt',
                'edition' => null,
                'published_year' => 2004,
                'pages' => 105,
                'illustration_pages' => null,
                'book_size' => '17x21cm',
                'price' => 7600,
                'quantity' => 1,
                'summary' => 'Sách bài tập và trò chơi Toán lớp 1.',
                'notes' => null,
                'series_name' => null,
                'publisher_place' => 'Hà Nội',
                'cabinet' => null,
                'shelf' => null,
                'classification' => $class,
                'detail' => $classDetail,
                'authors' => ['Đỗ Tiến Đạt'],
                'publishers' => ['Giáo dục'],
            ],
            [
                'registration_number' => 'DKCB0002',
                'book_code' => '5-51-0002',
                'title' => '112 trò chơi Toán lớp 1, lớp 2',
                'sub_title' => null,
                'language' => 'Tiếng Việt',
                'edition' => null,
                'published_year' => 2003,
                'pages' => 247,
                'illustration_pages' => null,
                'book_size' => '16x24cm',
                'price' => 20000,
                'quantity' => 1,
                'summary' => '112 trò chơi giúp học sinh lớp 1,2 học tốt môn Toán.',
                'notes' => null,
                'series_name' => null,
                'publisher_place' => 'Hà Nội',
                'cabinet' => null,
                'shelf' => null,
                'classification' => $class,
                'detail' => $classDetail,
                'authors' => ['Huỳnh Châu'],
                'publishers' => ['Đại học Sư phạm'],
            ],
            [
                'registration_number' => 'DKCB0003',
                'book_code' => '5-51-0003',
                'title' => '500 bài tập Toán cơ bản và nâng cao lớp 1',
                'sub_title' => null,
                'language' => 'Tiếng Việt',
                'edition' => null,
                'published_year' => 2005,
                'pages' => 127,
                'illustration_pages' => null,
                'book_size' => '16x24cm',
                'price' => 13000,
                'quantity' => 2,
                'summary' => '500 bài tập Toán lớp 1 từ cơ bản đến nâng cao.',
                'notes' => null,
                'series_name' => null,
                'publisher_place' => 'TP Hồ Chí Minh',
                'cabinet' => null,
                'shelf' => null,
                'classification' => $class,
                'detail' => $classDetail,
                'authors' => ['Nguyễn Đức Tấn'],
                'publishers' => ['Tổng Hợp'],
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

