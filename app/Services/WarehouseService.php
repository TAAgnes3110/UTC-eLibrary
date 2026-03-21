<?php

namespace App\Services;

use App\Exports\WarehouseExport;
use App\Imports\WarehouseImport;
use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator as PaginationLengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WarehouseService
{
    private const PER_PAGE = 50;
    
    /**
     * Tạo kho mới
     * @param array $data
     * @return Warehouse
     */
    public function create(array $data): Warehouse
    {
        $warehouse = Warehouse::create($data);
        MasterDataService::clearCache();

        return $warehouse;
    }

    /**
     * Cập nhật kho
     * @param Warehouse $warehouse
     * @param array $data
     * @return Warehouse
     */
    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        unset($data['id']);
        $warehouse->update($data);
        MasterDataService::clearCache();

        return $warehouse;
    }

    /**
     * Danh sách kho
     * @param ?string $keyword
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function index(?string $keyword, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        if ($keyword === null || $keyword === '') {
            $page = max(1, (int) request()->input('page', 1));
            /** @var PaginationLengthAwarePaginator $paginator */
            $paginator = MasterLookupCacheService::remember(
                "warehouses:index:page:{$page}:per-page:{$perPage}",
                function () use ($perPage) {
                    $query = Warehouse::query()
                        ->with('parent:id,code,name')
                        ->orderByDesc('id');

                    return $query->paginate($perPage)->withQueryString();
                }
            );

            return $paginator;
        }

        $query = Warehouse::query()
            ->with('parent:id,code,name')
            ->when($keyword !== null && $keyword !== '', fn ($q) => $q->where(function ($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%");
            }))
            ->orderByDesc('id');

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Xóa mềm kho
     * @param Warehouse $warehouse
     * @return void
     */
    public function destroy(Warehouse $warehouse): void
    {
        $warehouse->delete();
        MasterDataService::clearCache();
    }

    /**
     * Danh sách kho đã xóa
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function trash(int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        return Warehouse::onlyTrashed()
            ->with('parent:id,code,name')
            ->orderByDesc('deleted_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Khôi phục kho
     * @param int $id
     * @return ?Warehouse
     */
    public function restore(int $id): ?Warehouse
    {
        $warehouse = Warehouse::onlyTrashed()->find($id);
        if (!$warehouse) {
            return null;
        }
        $warehouse->restore();
        MasterDataService::clearCache();

        return $warehouse;
    }

    /** @return int số bản ghi đã khôi phục */
    public function restoreMany(array $ids): int
    {
        $ids = array_values(array_filter($ids, static fn ($v) => is_numeric($v)));
        if (empty($ids)) {
            return 0;
        }
        $restored = (int) Warehouse::onlyTrashed()->whereIn('id', $ids)->restore();
        if ($restored > 0) {
            MasterDataService::clearCache();
        }

        return $restored;
    }

    /**
     * Xóa vĩnh viễn kho
     * @param int $id
     * @return bool
     */
    public function forceDelete(int $id): bool
    {
        $warehouse = Warehouse::onlyTrashed()->find($id);
        if (!$warehouse) {
            return false;
        }
        $warehouse->forceDelete();
        MasterDataService::clearCache();

        return true;
    }

    /** @return int số bản ghi đã xóa vĩnh viễn */
    public function forceDeleteMany(array $ids): int
    {
        $ids = array_values(array_filter($ids, static fn ($v) => is_numeric($v)));
        if (empty($ids)) {
            return 0;
        }
        $deleted = Warehouse::onlyTrashed()->whereIn('id', $ids)->forceDelete();
        if ($deleted > 0) {
            MasterDataService::clearCache();
        }

        return $deleted;
    }

    /**
     * Cập nhật trạng thái kho
     * @param array $ids
     * @param bool $isActive
     * @return void
     */
    public function updateStatus(array $ids, bool $isActive): void
    {
        Warehouse::query()->whereIn('id', $ids)->update(['is_active' => $isActive]);
        MasterDataService::clearCache();
    }

    /**
     * Chuyển đổi trạng thái kho
     * @param int $id
     * @return ?array{is_active: bool} null nếu lỗi (kho không tồn tại)
     */
    public function toggleStatus(int $id): ?array
    {
        $warehouse = Warehouse::find($id);
        if (!$warehouse) {
            return null;
        }
        $warehouse->is_active = !$warehouse->is_active;
        $warehouse->save();
        MasterDataService::clearCache();

        return ['is_active' => $warehouse->is_active];
    }

    /**
     * Danh sách kho
     * @param int $perPage
     * @return array{warehouses: LengthAwarePaginator}
     */
    public function warehouseList(int $perPage = 20): array
    {
        $page = max(1, (int) request()->input('page', 1));
        /** @var PaginationLengthAwarePaginator $warehouses */
        $warehouses = MasterLookupCacheService::remember(
            "warehouses:list:active:page:{$page}:per-page:{$perPage}",
            function () use ($perPage) {
                return Warehouse::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->paginate($perPage)
                    ->withQueryString();
            }
        );
        return [
            'warehouses' => $warehouses,
        ];
    }

    /**
     * Danh sách kho đã xóa
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function trashList(int $perPage = 20): array
    {
        $warehouses = Warehouse::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->paginate($perPage)
            ->withQueryString();
        return [
            'warehouses' => $warehouses,
        ];
    }

    /**
     * Import danh sách kho từ file Excel/CSV (sync).
     *
     * @return array
     */
    public function importWarehouses(UploadedFile $file): array
    {
        $summary = WarehouseImport::import($file);
        MasterDataService::clearCache();

        return $summary;
    }

    public function exportWarehouses(?array $ids = null): StreamedResponse
    {
        return WarehouseExport::stream($ids);
    }
}