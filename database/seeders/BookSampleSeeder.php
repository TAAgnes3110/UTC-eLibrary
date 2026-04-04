<?php

namespace Database\Seeders;

use App\Enums\AccessMode;
use App\Enums\ResourceType;
use App\Models\Author;
use App\Models\Book;
use App\Models\Classification;
use App\Models\ClassificationDetail;
use App\Models\Publisher;
use App\Models\ThesisMetadata;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BookSampleSeeder extends Seeder
{
    public function run(): void
    {
        $class = Classification::firstOrCreate(
            ['code' => '624'],
            ['name' => 'Kết cấu, cầu đường bộ', 'params' => ['note' => 'Mã phân loại kỹ thuật cầu đường (mẫu).']]
        );

        $classDetail = ClassificationDetail::firstOrCreate(
            ['code' => '624.2'],
            ['name' => 'Cầu bê tông cốt thép', 'classification_id' => $class->id, 'params' => ['note' => 'Chi tiết cho cầu bê tông cốt thép.']]
        );

        $warehousePrint = Warehouse::firstOrCreate(
            ['code' => 'KHO-GT'],
            ['name' => 'Kho Giáo trình (Tầng 1 - Nhà A8)', 'is_active' => true, 'params' => ['campus' => 'Hà Nội', 'floor' => 1, 'type' => 'textbook']]
        );

        $warehouseDigital = Warehouse::firstOrCreate(
            ['code' => 'KHO-SO'],
            ['name' => 'Kho Tài liệu số', 'is_active' => true, 'params' => ['campus' => 'Hà Nội', 'floor' => null, 'type' => 'digital']]
        );

        $authors = [
            'Nguyễn Viết Trung',
            'Đỗ Bá Lâm',
            'Phạm Hữu Vinh',
            'Trần Thị Thanh',
            'Lê Minh Tuấn',
        ];

        $publishers = [
            'Giao thông Vận tải',
            'Xây dựng',
            'Khoa học và Kỹ thuật',
            'Đại học Giao thông Vận tải',
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

        $samples = [
            [
                'registration_number' => 'UTC0001',
                'book_code' => '6242-KHO-GT-0001',
                'title' => 'Cơ sở thiết kế đường ô tô',
                'sub_title' => null,
                'language' => 'Tiếng Việt',
                'edition' => null,
                'published_year' => 2018,
                'pages' => 350,
                'illustration_pages' => null,
                'book_size' => '19x27cm',
                'price' => 98000,
                'quantity' => 3,
                'summary' => 'Giáo trình cơ sở cho sinh viên ngành Kỹ thuật xây dựng công trình giao thông, trình bày các nguyên lý thiết kế đường ô tô.',
                'notes' => null,
                'series_name' => 'Giáo trình ĐH GTVT',
                'publisher_place' => 'Hà Nội',
                'cabinet' => 'GT1',
                'shelf' => 'A1',
                'resource_type' => ResourceType::TEXTBOOK->value,
                'access_mode' => AccessMode::CirculationOnly->value,
                'warehouse' => $warehousePrint,
                'classification' => $class,
                'detail' => $classDetail,
                'authors' => ['Nguyễn Viết Trung'],
                'publishers' => ['Giao thông Vận tải'],
                'thesis' => null,
            ],
            [
                'registration_number' => 'UTC-R001',
                'book_code' => '6242-KHO-GT-R001',
                'title' => 'Tài liệu tham khảo: Quy định an toàn kỹ thuật (bản mẫu)',
                'sub_title' => null,
                'language' => 'Tiếng Việt',
                'edition' => null,
                'published_year' => 2020,
                'pages' => 160,
                'illustration_pages' => null,
                'book_size' => '14x20cm',
                'price' => 45000,
                'quantity' => 6,
                'summary' => 'Tài liệu tham khảo dạng tra cứu nội bộ, dùng để minh hoạ dữ liệu mẫu cho resource_type = reference.',
                'notes' => 'Seed: reference',
                'series_name' => null,
                'publisher_place' => 'Hà Nội',
                'cabinet' => 'REF1',
                'shelf' => 'E1',
                'resource_type' => ResourceType::REFERENCE->value,
                'access_mode' => AccessMode::CirculationOnly->value,
                'warehouse' => $warehousePrint,
                'classification' => $class,
                'detail' => $classDetail,
                'authors' => ['Trần Thị Thanh'],
                'publishers' => ['Xây dựng'],
                'thesis' => null,
            ],
            [
                'registration_number' => 'UTC0002',
                'book_code' => '6242-KHO-GT-0002',
                'title' => 'Tổ chức vận tải và dịch vụ logistics',
                'sub_title' => null,
                'language' => 'Tiếng Việt',
                'edition' => null,
                'published_year' => 2019,
                'pages' => 420,
                'illustration_pages' => null,
                'book_size' => '16x24cm',
                'price' => 120000,
                'quantity' => 2,
                'summary' => 'Tài liệu phục vụ các ngành Vận tải – Kinh tế, trình bày nguyên lý tổ chức vận tải và quản lý chuỗi cung ứng, logistics.',
                'notes' => null,
                'series_name' => null,
                'publisher_place' => 'Hà Nội',
                'cabinet' => 'VT1',
                'shelf' => 'B2',
                'resource_type' => ResourceType::TEXTBOOK->value,
                'access_mode' => AccessMode::CirculationOnly->value,
                'warehouse' => $warehousePrint,
                'classification' => $class,
                'detail' => $classDetail,
                'authors' => ['Đỗ Bá Lâm'],
                'publishers' => ['Giao thông Vận tải'],
                'thesis' => null,
            ],
            [
                'registration_number' => 'UTC0003',
                'book_code' => '6242-KHO-GT-0003',
                'title' => 'Kết cấu bê tông cốt thép – Cầu đường bộ',
                'sub_title' => null,
                'language' => 'Tiếng Việt',
                'edition' => null,
                'published_year' => 2017,
                'pages' => 290,
                'illustration_pages' => null,
                'book_size' => '19x27cm',
                'price' => 135000,
                'quantity' => 4,
                'summary' => 'Giáo trình chuyên sâu về thiết kế và kiểm toán kết cấu bê tông cốt thép trong công trình cầu đường bộ.',
                'notes' => null,
                'series_name' => null,
                'publisher_place' => 'Hà Nội',
                'cabinet' => 'CT1',
                'shelf' => 'C3',
                'resource_type' => ResourceType::TEXTBOOK->value,
                'access_mode' => AccessMode::CirculationOnly->value,
                'warehouse' => $warehousePrint,
                'classification' => $class,
                'detail' => $classDetail,
                'authors' => ['Phạm Hữu Vinh', 'Trần Thị Thanh'],
                'publishers' => ['Xây dựng'],
                'thesis' => null,
            ],
            [
                'registration_number' => 'UTC-J001',
                'book_code' => '6242-KHO-SO-J001',
                'title' => 'Tạp chí chuyên ngành giao thông vận tải (bản PDF mẫu)',
                'sub_title' => 'Số phát hành mô phỏng',
                'language' => 'Tiếng Việt',
                'edition' => 'Số đặc biệt',
                'published_year' => 2021,
                'pages' => 90,
                'illustration_pages' => null,
                'book_size' => null,
                'price' => 0,
                'quantity' => 0,
                'summary' => 'Tài liệu dạng tạp chí số (journal) để minh hoạ filter resource_type = journal ở trang in.',
                'notes' => 'Seed: journal',
                'series_name' => null,
                'publisher_place' => 'Hà Nội',
                'cabinet' => null,
                'shelf' => null,
                'resource_type' => ResourceType::JOURNAL->value,
                'access_mode' => AccessMode::OnlineOnly->value,
                'warehouse' => $warehouseDigital,
                'classification' => $class,
                'detail' => $classDetail,
                'authors' => [],
                'publishers' => ['Giao thông Vận tải'],
                'thesis' => null,
            ],
            [
                'registration_number' => 'UTC-D001',
                'book_code' => '6242-KHO-SO-0001',
                'title' => 'Quy chuẩn kỹ thuật quốc gia về đường bộ (bản PDF mẫu)',
                'sub_title' => 'Tài liệu số nội bộ',
                'language' => 'Tiếng Việt',
                'edition' => '2023',
                'published_year' => 2023,
                'pages' => 180,
                'illustration_pages' => null,
                'book_size' => null,
                'price' => 0,
                'quantity' => 0,
                'summary' => 'Tài liệu số tham khảo — đăng ký trong hệ thống thư viện điện tử UTC (file PDF do thủ thư tải lên sau khi seed).',
                'notes' => 'Seed: resource_type = digital',
                'series_name' => null,
                'publisher_place' => 'Hà Nội',
                'cabinet' => null,
                'shelf' => null,
                'resource_type' => ResourceType::DIGITAL->value,
                'access_mode' => AccessMode::OnlineOnly->value,
                'warehouse' => $warehouseDigital,
                'classification' => $class,
                'detail' => $classDetail,
                'authors' => ['Lê Minh Tuấn'],
                'publishers' => ['Đại học Giao thông Vận tải'],
                'thesis' => null,
            ],
            [
                'registration_number' => 'UTC-D002',
                'book_code' => '6242-KHO-SO-0002',
                'title' => 'Đồ án tốt nghiệp: Thiết kế nút giao đường bộ (fulltext mẫu)',
                'sub_title' => 'Khóa K65 – ngành Kỹ thuật xây dựng công trình GTVT',
                'language' => 'Tiếng Việt',
                'edition' => null,
                'published_year' => 2024,
                'pages' => 85,
                'illustration_pages' => null,
                'book_size' => null,
                'price' => 0,
                'quantity' => 0,
                'summary' => 'Luận văn/đồ án mẫu trong kho tài liệu số — có metadata luận (GVHD, năm bảo vệ).',
                'notes' => null,
                'series_name' => null,
                'publisher_place' => 'Hà Nội',
                'cabinet' => null,
                'shelf' => null,
                'resource_type' => ResourceType::THESIS->value,
                'access_mode' => AccessMode::OnlineOnly->value,
                'warehouse' => $warehouseDigital,
                'classification' => $class,
                'detail' => $classDetail,
                'authors' => ['Lê Minh Tuấn'],
                'publishers' => ['Đại học Giao thông Vận tải'],
                'thesis' => [
                    'work_type' => 'undergraduate_thesis',
                    'degree_program' => 'Kỹ thuật xây dựng công trình giao thông',
                    'supervisor_name' => 'PGS.TS. Nguyễn Viết Trung',
                    'defense_year' => 2024,
                    'keywords' => 'nút giao, đường bộ, GTVT',
                    'abstract_text' => 'Đồ án trình bày phương án thiết kế nút giao đồng mức tại khu vực ngoại thành (dữ liệu mẫu).',
                ],
            ],
            [
                'registration_number' => 'UTC-H001',
                'book_code' => '6242-KHO-GT-0004',
                'title' => 'An toàn giao thông đường bộ (bản in kèm ebook)',
                'sub_title' => null,
                'language' => 'Tiếng Việt',
                'edition' => null,
                'published_year' => 2022,
                'pages' => 200,
                'illustration_pages' => null,
                'book_size' => '17x24cm',
                'price' => 75000,
                'quantity' => 5,
                'summary' => 'Giáo trình có bản in tại thư viện và bản điện tử tra cứu (hybrid).',
                'notes' => 'Seed: hybrid',
                'series_name' => null,
                'publisher_place' => 'Hà Nội',
                'cabinet' => 'GT2',
                'shelf' => 'D1',
                'resource_type' => ResourceType::TEXTBOOK->value,
                'access_mode' => AccessMode::Both->value,
                'warehouse' => $warehousePrint,
                'classification' => $class,
                'detail' => $classDetail,
                'authors' => ['Trần Thị Thanh'],
                'publishers' => ['Giao thông Vận tải'],
                'thesis' => null,
            ],
        ];

        foreach ($samples as $row) {
            $book = Book::updateOrCreate(
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
                    'warehouse_id' => $row['warehouse']->id,
                    'resource_type' => $row['resource_type'],
                    'access_mode' => $row['access_mode'],
                    'params' => [],
                ]
            );

            $authorIds = [];
            foreach ($row['authors'] as $name) {
                $a = $authorModels[$name] ?? Author::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name, 'params' => []]
                );
                $authorIds[$a->id] = ['order' => count($authorIds)];
            }
            if ($authorIds !== []) {
                $book->authors()->sync($authorIds);
            }

            $publisherIds = [];
            foreach ($row['publishers'] as $name) {
                $p = $publisherModels[$name] ?? Publisher::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name, 'params' => []]
                );
                $publisherIds[$p->id] = ['order' => count($publisherIds)];
            }
            if ($publisherIds !== []) {
                $book->publishers()->sync($publisherIds);
            }

            if (! empty($row['thesis'])) {
                ThesisMetadata::updateOrCreate(
                    ['book_id' => $book->id],
                    array_merge($row['thesis'], ['book_id' => $book->id])
                );
            } else {
                ThesisMetadata::withTrashed()->where('book_id', $book->id)->forceDelete();
            }
        }
    }
}
