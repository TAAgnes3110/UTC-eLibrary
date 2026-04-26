<?php

namespace Database\Seeders;

use App\Enums\AccessMode;
use App\Enums\ResourceType;
use App\Models\Author;
use App\Models\Book;
use App\Models\Classification;
use App\Models\Publisher;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BookSampleSeeder extends Seeder
{
    private const TITLES_PER_WAREHOUSE = 250;

    public function run(): void
    {
        $classifications = Classification::query()
            ->orderBy('id')
            ->orderBy('code')
            ->get();
        if ($classifications->isEmpty()) {
            return;
        }

        $warehouseTextbook = Warehouse::firstOrCreate(
            ['code' => 'KHO-GT'],
            ['name' => 'Kho Giáo trình', 'is_active' => true, 'params' => ['campus' => 'Hà Nội', 'floor' => 1, 'building' => 'A8', 'type' => 'textbook', 'note' => 'Tầng 1 - Nhà A8']]
        );

        $warehouseReference = Warehouse::firstOrCreate(
            ['code' => 'KHO-TK'],
            ['name' => 'Kho Sách tham khảo & Luận án', 'is_active' => true, 'params' => ['campus' => 'Hà Nội', 'floor' => '5-6', 'building' => 'A8', 'type' => 'reference', 'note' => 'Tầng 5-6 - Nhà A8']]
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

        $textbookPrefixes = ['Giáo trình', 'Bài giảng', 'Thực hành', 'Bài tập', 'Nhập môn'];
        $referencePrefixes = ['Sổ tay', 'Chuyên khảo', 'Cẩm nang', 'Hướng dẫn', 'Tổng quan'];
        $topics = ['ứng dụng', 'nâng cao', 'cơ bản', 'thực tiễn', 'phân tích', 'thiết kế', 'quản lý', 'hiện đại'];

        $configs = [
            [
                'warehouse' => $warehouseTextbook,
                'resource_type' => ResourceType::TEXTBOOK->value,
                'prefixes' => $textbookPrefixes,
                'book_code_prefix' => 'GT',
                'reg_prefix' => 'UTCGT',
            ],
            [
                'warehouse' => $warehouseReference,
                'resource_type' => ResourceType::REFERENCE->value,
                'prefixes' => $referencePrefixes,
                'book_code_prefix' => 'TK',
                'reg_prefix' => 'UTCTK',
            ],
        ];

        foreach ($configs as $config) {
            for ($i = 0; $i < self::TITLES_PER_WAREHOUSE; $i++) {
                $classification = $classifications[$i % $classifications->count()];

                $prefix = $config['prefixes'][$i % count($config['prefixes'])];
                $topic = $topics[$i % count($topics)];
                $title = sprintf(
                    '%s %s - %s %03d',
                    $prefix,
                    $classification->name,
                    $topic,
                    $i + 1
                );

                $authorNames = [
                    $authors[$i % count($authors)],
                    $authors[($i + 2) % count($authors)],
                ];
                $publisherName = $publishers[$i % count($publishers)];
                $sequence = $i + 1;

                $row = [
                    'registration_number' => sprintf('%s%04d', $config['reg_prefix'], $sequence),
                    'book_code' => sprintf('%s-BOOK-%04d', $config['book_code_prefix'], $sequence),
                    'title' => $title,
                    'sub_title' => null,
                    'language' => 'Tiếng Việt',
                    'edition' => null,
                    'published_year' => 2010 + ($i % 15),
                    'pages' => 120 + (($i * 9) % 260),
                    'illustration_pages' => null,
                    'book_size' => '16x24cm',
                    'price' => 70000 + ($i * 1200),
                    'quantity' => 2 + ($i % 6),
                    'summary' => 'Dữ liệu mẫu chuẩn hóa cho quản lý kho và tủ thư viện UTC.',
                    'notes' => null,
                    'publisher_place' => 'Hà Nội',
                    'cabinet' => $classification->code,
                    'resource_type' => $config['resource_type'],
                    'access_mode' => AccessMode::CirculationOnly->value,
                    'warehouse' => $config['warehouse'],
                    'classification' => $classification,
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
                        'classification_id' => $row['classification']->id,
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
}
