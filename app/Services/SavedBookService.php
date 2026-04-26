<?php

namespace App\Services;

use App\Models\Book;
use App\Models\SavedBook;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SavedBookService
{
    public function isSaved(User $user, int $bookId): bool
    {
        return SavedBook::query()
            ->where('user_id', $user->id)
            ->where('book_id', $bookId)
            ->exists();
    }

    public function save(User $user, Book $book): void
    {
        SavedBook::query()->firstOrCreate([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function unsave(User $user, Book $book): void
    {
        SavedBook::query()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->delete();
    }

    /**
     * @return LengthAwarePaginator<int, SavedBook>
     */
    public function paginateForUser(User $user, int $perPage = 12): LengthAwarePaginator
    {
        $perPage = min(max($perPage, 1), 60);

        return SavedBook::query()
            ->where('user_id', $user->id)
            ->whereHas('book', fn (Builder $q) => $q->whereNull('deleted_at'))
            ->with([
                'book' => function ($q): void {
                    $q->with(['classification:id,name', 'authors:id,name']);
                },
            ])
            ->latest('saved_books.created_at')
            ->paginate($perPage)
            ->withQueryString();
    }
}
