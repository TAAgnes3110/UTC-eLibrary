<?php

namespace Database\Seeders;

use App\Enums\AccessMode;
use App\Enums\ResourceType;
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

        $titles = [
            'Cơ sở thiết kế đường ô tô',
            'Kết cấu bê tông cốt thép cầu đường',
            'Nền móng công trình giao thông',
            'Tổ chức thi công đường bộ',
            'An toàn giao thông đô thị',
            'Khai thác và bảo trì đường ô tô',
            'Kinh tế vận tải',
            'Logistics và chuỗi cung ứng',
            'Vật liệu xây dựng giao thông',
            'Quy hoạch giao thông vận tải',
            'Đường sắt đô thị cơ bản',
            'Cơ học đất ứng dụng',
            'Thí nghiệm vật liệu cầu đường',
            'Thiết kế nút giao thông',
            'Quản lý dự án hạ tầng giao thông',
            'Hệ thống giao thông thông minh ITS',
            'Đánh giá an toàn công trình cầu',
            'Công nghệ thi công mặt đường',
            'Tiêu chuẩn thiết kế cầu hiện đại',
            'Địa kỹ thuật công trình',
            'Sổ tay tham khảo quy chuẩn đường bộ',
            'Sổ tay tra cứu kết cấu cầu thép',
            'Tạp chí giao thông vận tải - Chuyên đề 1',
            'Tạp chí giao thông vận tải - Chuyên đề 2',
        ];

        foreach ($titles as $idx => $title) {
            $resourceType = match (true) {
                $idx >= 22 => ResourceType::JOURNAL->value,
                $idx >= 20 => ResourceType::REFERENCE->value,
                default => ResourceType::TEXTBOOK->value,
            };
            $warehouse = $idx >= 22 ? $warehouseDigital : $warehousePrint;
            $authorNames = [
                $authors[$idx % count($authors)],
                $authors[($idx + 2) % count($authors)],
            ];
            $publisherName = $publishers[$idx % count($publishers)];

            $row = [
                'registration_number' => sprintf('UTCVN%04d', $idx + 1),
                'book_code' => sprintf('VN-BOOK-%04d', $idx + 1),
                'title' => $title,
                'sub_title' => null,
                'language' => 'Tiếng Việt',
                'edition' => null,
                'published_year' => 2015 + ($idx % 10),
                'pages' => 150 + (($idx * 7) % 220),
                'illustration_pages' => null,
                'book_size' => '16x24cm',
                'price' => 70000 + ($idx * 3500),
                'quantity' => $resourceType === ResourceType::JOURNAL->value ? 1 : 2 + ($idx % 4),
                'summary' => 'Giáo trình/tài liệu tiếng Việt dùng cho dữ liệu mẫu thư viện UTC.',
                'notes' => null,
                'publisher_place' => 'Hà Nội',
                'cabinet' => $resourceType === ResourceType::JOURNAL->value ? null : 'KHO1',
                'shelf' => $resourceType === ResourceType::JOURNAL->value ? null : 'A'.(($idx % 8) + 1),
                'resource_type' => $resourceType,
                'access_mode' => AccessMode::CirculationOnly->value,
                'warehouse' => $warehouse,
                'classification' => $class,
                'detail' => $classDetail,
                'authors' => $authorNames,
                'publishers' => [$publisherName],
            ];

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
        }
    }
}
