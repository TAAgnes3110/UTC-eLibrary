<?php

namespace App\Services;

use App\Exports\WarehouseExport;
use App\Imports\WarehouseImport;
use App\Models\BookshelfCell;
use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator as PaginationLengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WarehouseService
{
    private const PER_PAGE = 50;

    /**
     * Tạo kho mới
     */
    public function create(array $data): Warehouse
    {
        $shelfCount = (int) ($data['shelf_count'] ?? 0);
        unset($data['shelf_count']);

        $warehouse = Warehouse::create($data);
        if ($shelfCount > 0) {
            $this->seedShelvesForWarehouse($warehouse, $shelfCount);
        }
        MasterDataService::clearCache();

        return $warehouse;
    }

    private function seedShelvesForWarehouse(Warehouse $warehouse, int $shelfCount): void
    {
        $shelfCount = min(max($shelfCount, 0), 50);
        if ($shelfCount === 0) {
            return;
        }

        DB::transaction(function () use ($warehouse, $shelfCount): void {
            for ($idx = 1; $idx <= $shelfCount; $idx++) {
                $rowIndex = intdiv($idx - 1, 20) + 1;
                $columnIndex = (($idx - 1) % 20) + 1;
                $label = sprintf('R%02d-C%02d', $rowIndex, $columnIndex);

                BookshelfCell::query()->updateOrCreate(
                    [
                        'warehouse_id' => $warehouse->id,
                        'row_index' => $rowIndex,
                        'column_index' => $columnIndex,
                    ],
                    [
                        'label' => $label,
                        'current_quantity' => 0,
                        'classification_id' => null,
                        'classification_detail_id' => null,
                        'is_active' => true,
                        'params' => [
                            'auto_seeded_on_warehouse_create' => true,
                            'books_per_rack' => 30,
                        ],
                    ]
                );
            }
        });
    }

    /**
     * Cập nhật kho
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
     */
    /**
     * @param  list<string>|null  $keywordColumns
     */
    public function index(?string $keyword, int $perPage = self::PER_PAGE, ?array $keywordColumns = null): LengthAwarePaginator
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
            ->when($keyword !== null && $keyword !== '', function ($q) use ($keyword, $keywordColumns) {
                $effectiveColumns = ! empty($keywordColumns)
                    ? $keywordColumns
                    : ['code', 'name'];
                $q->where(function ($sub) use ($keyword, $effectiveColumns) {
                    $applied = false;
                    if (in_array('code', $effectiveColumns, true)) {
                        $sub->where('code', 'like', "%{$keyword}%");
                        $applied = true;
                    }
                    if (in_array('name', $effectiveColumns, true)) {
                        $method = $applied ? 'orWhere' : 'where';
                        $sub->{$method}('name', 'like', "%{$keyword}%");
                    }
                });
            })
            ->orderByDesc('id');

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Xóa mềm kho
     */
    public function destroy(Warehouse $warehouse): void
    {
        $warehouse->delete();
        MasterDataService::clearCache();
    }

    /**
     * Danh sách kho đã xóa
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
     */
    public function restore(int $id): ?Warehouse
    {
        $warehouse = Warehouse::onlyTrashed()->find($id);
        if (! $warehouse) {
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
     */
    public function forceDelete(int $id): bool
    {
        $warehouse = Warehouse::onlyTrashed()->find($id);
        if (! $warehouse) {
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
     */
    public function updateStatus(array $ids, bool $isActive): void
    {
        Warehouse::query()->whereIn('id', $ids)->update(['is_active' => $isActive]);
        MasterDataService::clearCache();
    }

    /**
     * Chuyển đổi trạng thái kho
     *
     * @return ?array{is_active: bool} null nếu lỗi (kho không tồn tại)
     */
    public function toggleStatus(int $id): ?array
    {
        $warehouse = Warehouse::find($id);
        if (! $warehouse) {
            return null;
        }
        $warehouse->is_active = ! $warehouse->is_active;
        $warehouse->save();
        MasterDataService::clearCache();

        return ['is_active' => $warehouse->is_active];
    }

    /**
     * Danh sách kho
     *
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
     *
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
