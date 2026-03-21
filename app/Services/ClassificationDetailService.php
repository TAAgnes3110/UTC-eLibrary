<?php

namespace App\Services;

use App\Models\ClassificationDetail;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as PaginationLengthAwarePaginator;

class ClassificationDetailService
{
    private const PER_PAGE = 50;

    public function create(array $data): ClassificationDetail
    {
        $detail = ClassificationDetail::create($data);
        MasterDataService::clearCache();
        return $detail;
    }

    public function update(ClassificationDetail $detail, array $data): ClassificationDetail
    {
        unset($data['id'], $data['created_at'], $data['updated_at']);
        $detail->update($data);
        MasterDataService::clearCache();
        return $detail;
    }

    public function index(?string $keyword, ?int $classificationId, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        if (($keyword === null || $keyword === '') && $perPage >= 100) {
            $page = max(1, (int) request()->input('page', 1));
            $classificationKey = $classificationId === null ? 'all' : (string) $classificationId;
            /** @var PaginationLengthAwarePaginator $paginator */
            $paginator = MasterLookupCacheService::remember(
                "classification-details:index:{$classificationKey}:page:{$page}:per-page:{$perPage}",
                function () use ($classificationId, $perPage) {
                    return ClassificationDetail::query()
                        ->with(['classification:id,code,name', 'parent:id,code,name'])
                        ->when($classificationId !== null, fn ($q) => $q->where('classification_id', $classificationId))
                        ->orderBy('classification_id')
                        ->orderBy('code')
                        ->paginate($perPage)
                        ->withQueryString();
                }
            );

            return $paginator;
        }

        return ClassificationDetail::query()
            ->with(['classification:id,code,name', 'parent:id,code,name'])
            ->when($classificationId !== null, fn ($q) => $q->where('classification_id', $classificationId))
            ->when($keyword !== null && $keyword !== '', fn ($q) => $q->where(function ($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%");
            }))
            ->orderBy('classification_id')
            ->orderBy('code')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Danh sách chi tiết theo classification_id (cho dropdown).
     */
    public function listByClassification(int $classificationId): Collection
    {
        /** @var Collection $items */
        $items = MasterLookupCacheService::remember("classification-details:by-classification:{$classificationId}", function () use ($classificationId) {
            return ClassificationDetail::where('classification_id', $classificationId)
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'classification_id']);
        });

        return $items;
    }

    public function destroy(ClassificationDetail $detail): void
    {
        $detail->delete();
        MasterDataService::clearCache();
    }
}
