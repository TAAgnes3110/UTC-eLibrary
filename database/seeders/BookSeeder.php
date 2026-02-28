<?php

namespace Database\Seeders;

use App\Enums\BookType;
use App\Models\Author;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\Publisher;
use Illuminate\Database\Seeder;

/** Dữ liệu mẫu: Sách + bản in (để test API loans). */
class BookSeeder extends Seeder
{
    public function run(): void
    {
        $publisher = Publisher::first();
        $categoryVH = Category::where('code', 'VH')->first();
        $categoryTK = Category::where('code', 'TK')->first();
        $author1 = Author::where('name', 'Nguyễn Nhật Ánh')->first();
        $author2 = Author::where('name', 'Tô Hoài')->first();
        $author3 = Author::where('name', 'Nam Cao')->first();

        $books = [
            [
                'title' => 'Cho tôi xin một vé đi tuổi thơ',
                'type' => BookType::BOOK,
                'isbn' => '978-604-1-00001-1',
                'classification_code' => 'VH-001',
                'language' => 'vi',
                'edition' => 'Tái bản 2023',
                'published_year' => 2023,
                'total_pages' => 280,
                'price' => 85000,
                'author_name' => 'Nguyễn Nhật Ánh',
                'copies' => 2,
            ],
            [
                'title' => 'Dế mèn phiêu lưu ký',
                'type' => BookType::BOOK,
                'isbn' => '978-604-1-00002-2',
                'classification_code' => 'VH-002',
                'language' => 'vi',
                'published_year' => 2022,
                'total_pages' => 150,
                'price' => 45000,
                'author_name' => 'Tô Hoài',
                'copies' => 3,
            ],
            [
                'title' => 'Chí Phèo',
                'type' => BookType::BOOK,
                'isbn' => '978-604-1-00003-3',
                'classification_code' => 'VH-003',
                'language' => 'vi',
                'published_year' => 2020,
                'total_pages' => 120,
                'price' => 35000,
                'author_name' => 'Nam Cao',
                'copies' => 2,
            ],
            [
                'title' => 'Giáo trình Cấu trúc dữ liệu và giải thuật',
                'type' => BookType::TEXTBOOK,
                'isbn' => '978-604-1-00100-1',
                'classification_code' => 'TK-010',
                'language' => 'vi',
                'edition' => 'Lần 2',
                'published_year' => 2023,
                'total_pages' => 400,
                'price' => 120000,
                'author_name' => 'Nguyễn Nhật Ánh', // dùng tác giả có sẵn
                'copies' => 5,
            ],
            [
                'title' => 'Lập trình hướng đối tượng với Java',
                'type' => BookType::TEXTBOOK,
                'isbn' => '978-604-1-00101-2',
                'classification_code' => 'TK-011',
                'language' => 'vi',
                'published_year' => 2022,
                'total_pages' => 350,
                'price' => 95000,
                'author_name' => 'Nguyễn Nhật Ánh',
                'copies' => 4,
            ],
        ];

        foreach ($books as $i => $data) {
            $authorName = $data['author_name'];
            unset($data['author_name']);
            $copies = $data['copies'];
            unset($data['copies']);

            $author = Author::where('name', $authorName)->first();
            $book = Book::firstOrCreate(
                ['isbn' => $data['isbn']],
                [
                    'title' => $data['title'],
                    'type' => $data['type'],
                    'classification_code' => $data['classification_code'],
                    'language' => $data['language'] ?? null,
                    'edition' => $data['edition'] ?? null,
                    'category_id' => ($data['classification_code'] ?? '')[0] === 'V' ? $categoryVH?->id : $categoryTK?->id,
                    'publisher_id' => $publisher?->id,
                    'publication_place' => 'Hà Nội',
                    'published_year' => $data['published_year'],
                    'total_pages' => $data['total_pages'],
                    'price' => $data['price'],
                    'status' => 'available',
                ]
            );

            if ($author && !$book->authors()->where('author_id', $author->id)->exists()) {
                $book->authors()->attach($author->id, ['role' => 'author', 'order' => 1]);
            }

            $existingCopies = $book->copies()->count();
            for ($j = $existingCopies; $j < $copies; $j++) {
                $barcode = 'UTC-B' . $book->id . 'C' . ($j + 1);
                BookCopy::firstOrCreate(
                    ['barcode' => $barcode],
                    [
                        'book_id' => $book->id,
                        'condition' => 'good',
                        'status' => 'available',
                    ]
                );
            }
            $book->updateStatistics();
        }
    }
}
