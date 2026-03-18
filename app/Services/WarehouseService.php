<?php

namespace App\Services;

use App\Exports\WarehouseExport;
use App\Imports\WarehouseImport;
use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
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
        return Warehouse::create($data);
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
        return $warehouse;
    }

    /** @return int số bản ghi đã khôi phục */
    public function restoreMany(array $ids): int
    {
        $ids = array_values(array_filter($ids, static fn ($v) => is_numeric($v)));
        if (empty($ids)) {
            return 0;
        }
        return (int) Warehouse::onlyTrashed()->whereIn('id', $ids)->restore();
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
        return true;
    }

    /** @return int số bản ghi đã xóa vĩnh viễn */
    public function forceDeleteMany(array $ids): int
    {
        $ids = array_values(array_filter($ids, static fn ($v) => is_numeric($v)));
        if (empty($ids)) {
            return 0;
        }
        return Warehouse::onlyTrashed()->whereIn('id', $ids)->forceDelete();
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
        return ['is_active' => $warehouse->is_active];
    }

    /**
     * Danh sách kho
     * @param int $perPage
     * @return array{warehouses: LengthAwarePaginator}
     */
    public function warehouseList(int $perPage = 20): array
    {
        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
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
        return WarehouseImport::import($file);
    }

    public function exportWarehouses(?array $ids = null): StreamedResponse
    {
        return WarehouseExport::stream($ids);
    }
}