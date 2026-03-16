<?php

namespace App\Services;

use App\Models\Book;

class BookService
{
    private const PER_PAGE = 50;

    public function create(array $data): Book
    {
        return Book::create($data);
    }
}