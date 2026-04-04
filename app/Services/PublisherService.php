<?php

namespace App\Services;

use App\Models\Publisher;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PublisherService
{
    private const PER_PAGE = 50;

    public function create(array $data): Publisher
    {
        return Publisher::create($data);
    }

    public function update(Publisher $publisher, array $data): Publisher
    {
        $publisher->fill($data);
        $publisher->save();

        return $publisher;
    }

    public function index(?string $keyword, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        $query = Publisher::query()
            ->when($keyword !== null && $keyword !== '', fn ($q) => $q->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%");
            }));

        return $query->paginate($perPage)->withQueryString();
    }

    public function delete(Publisher $publisher): void
    {
        DB::transaction(static function () use ($publisher) {
            $publisher->delete();
        });
    }
}
