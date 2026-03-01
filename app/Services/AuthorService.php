<?php

namespace App\Services;

use App\Helpers\FileHelpers;
use App\Imports\AuthorsImport;
use App\Models\Author;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class AuthorService
{
    public const TRASH_PER_PAGE = 50;

    public function count(): int
    {
        return Author::query()->count();
    }

    /** @return \Illuminate\Database\Eloquent\Collection */
    public function countBookByAuthor(): \Illuminate\Database\Eloquent\Collection
    {
        return Author::query()->withCount('books')->get();
    }

    public function destroy(Author $author): void
    {
        $author->delete();
    }

    public function list(?string $keyword = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = Author::query()
            ->when($keyword, function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%");
                if (is_numeric($keyword)) {
                    $q->orWhere('id', (int) $keyword);
                }
            })
            ->orderByDesc('id');

        return $query->paginate($perPage);
    }

    public function create(array $data): Author
    {
        if (Author::duplicate($data)->exists()) {
            throw new \InvalidArgumentException(__('messages.error_duplicate'));
        }
        return Author::create($data);
    }

    public function update(Author $author, array $data): Author
    {
        if (Author::duplicate($data, $author->id)->exists()) {
            throw new \InvalidArgumentException(__('messages.error_duplicate'));
        }
        $author->update($data);
        return $author->fresh();
    }

    public function import(UploadedFile $file): array
    {
        if (!FileHelpers::isExcelFile($file)) {
            throw new \InvalidArgumentException('File phải có định dạng: ' . implode(', ', FileHelpers::EXCEL_EXTENSIONS));
        }
        return (new AuthorsImport())->import($file);
    }

    public function trash(int $perPage = self::TRASH_PER_PAGE): LengthAwarePaginator
    {
        $paginator = Author::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->paginate($perPage, ['id', 'name', 'nationality', 'deleted_at']);

        $paginator->getCollection()->transform(fn ($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'nationality' => $a->nationality,
            'deleted_at' => $a->deleted_at?->toIso8601String(),
        ]);

        return $paginator;
    }

    public function restore(int $id): ?Author
    {
        $author = Author::onlyTrashed()->find($id);
        if ($author) {
            $author->restore();
        }
        return $author;
    }

    public function forceDelete(int $id): bool
    {
        $author = Author::onlyTrashed()->find($id);
        if ($author) {
            $author->forceDelete();
            return true;
        }
        return false;
    }
}
