<?php

namespace App\Services;

use App\Models\Author;
use Illuminate\Pagination\LengthAwarePaginator;

class AuthorService
{
    private const PER_PAGE = 50;

    public function create(array $data): Author
    {
        return Author::create($data);
    }

    public function update(Author $author, array $data): Author
    {
        $author->fill($data);
        $author->save();
        return $author;
    }

    public function index(?string $keyword, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        $query = Author::query()
            ->when($keyword !== null && $keyword !== '', fn ($q) => $q->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%");
            }));
        return $query->paginate($perPage)->withQueryString();
    }
}