<?php

namespace App\Services;

use App\Models\Classification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ClassificationService
{
    private const PER_PAGE = 50;

    public function create(array $data): Classification
    {
        $classification = Classification::create($data);
        MasterDataService::clearCache();

        return $classification;
    }

    public function update(Classification $classification, array $data): Classification
    {
        unset($data['id'], $data['created_at'], $data['updated_at']);
        $classification->update($data);
        MasterDataService::clearCache();

        return $classification;
    }

    public function index(?string $keyword, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        return Classification::query()
            ->with('parent:id,code,name')
            ->withCount('details')
            ->when($keyword !== null && $keyword !== '', fn ($q) => $q->where(function ($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%");
            }))
            ->orderBy('code')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listAll(): Collection
    {
        /** @var Collection $items */
        $items = MasterLookupCacheService::remember('classifications:list-all', function () {
            return Classification::orderBy('code')->get(['id', 'code', 'name']);
        });

        return $items;
    }

    public function destroy(Classification $classification): void
    {
        $classification->delete();
        MasterDataService::clearCache();
    }
}
