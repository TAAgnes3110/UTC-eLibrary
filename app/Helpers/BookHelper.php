<?php

namespace App\Helpers;

use App\Enums\BookType;
use App\Models\Author;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Publisher;
use Illuminate\Support\Str;

class BookHelper
{
    /**
     * @param array $data
     * @return int|null
     */
    public static function resolvePublisherId(array $data): ?int
    {
        if (!empty($data['publisher_id'])) {
            return (int) $data['publisher_id'];
        }
        $name = trim((string) ($data['publisher'] ?? ''));
        if ($name !== '') {
            $publisher = Publisher::firstOrCreate(
                ['name' => $name],
                ['country' => 'Việt Nam', 'is_active' => true]
            );
            return $publisher->id;
        }
        $type = BookType::tryFrom($data['type'] ?? '');
        if ($type && $type->useDefaultPublisher()) {
            $publisher = Publisher::getOrCreateDefaultPublisher();
            return $publisher->id;
        }
        return null;
    }

    /**
     * @param array $data
     * @return array
     */
    public static function resolveAuthorIds(array $data): array
    {
        $main = trim((string) ($data['author'] ?? ''));
        $co = trim((string) ($data['co_authors'] ?? ''));
        $names = array_filter(array_map('trim', array_merge([$main], $co !== '' ? preg_split('/[,;]+/u', $co) : [])));
        $names = array_unique($names);
        $ids = [];
        foreach ($names as $name) {
            if ($name === '') {
                continue;
            }
            $author = Author::firstOrCreate(['name' => $name], ['name' => $name]);
            $ids[] = $author->id;
        }
        return $ids;
    }

    /**
     * @param Book $book
     * @param array $authorIds
     * @return void
     */
    public static function syncAuthors(Book $book, array $authorIds): void
    {
        $sync = [];
        foreach (array_values($authorIds) as $order => $id) {
            $sync[$id] = ['role' => $order === 0 ? 'author' : 'co-author', 'order' => $order];
        }
        $book->authors()->sync($sync);
    }

    /**
     * @param Book $book
     * @param int $count
     * @return void
     */
    public static function createCopies(Book $book, int $count): void
    {
        $existing = BookCopy::withTrashed()->where('book_id', $book->id)->count();
        $prefix = 'BK-' . $book->id . '-';
        for ($i = 0; $i < $count; $i++) {
            $seq = $existing + $i + 1;
            $barcode = $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            if (BookCopy::where('barcode', $barcode)->exists()) {
                $barcode = $barcode . '-' . Str::random(6);
            }
            BookCopy::create([
                'book_id' => $book->id,
                'barcode' => $barcode,
                'status' => 'available',
                'condition' => 'good',
            ]);
        }
    }
}
