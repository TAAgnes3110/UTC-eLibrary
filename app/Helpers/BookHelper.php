<?php

namespace App\Helpers;

use App\Models\Book;
use App\Models\BookCopy;
use Illuminate\Support\Str;

class BookHelper
{
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
