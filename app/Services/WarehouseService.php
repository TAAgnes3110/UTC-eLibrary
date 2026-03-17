<?php

namespace App\Services;

use App\Enums\ImportType;
use App\Enums\ImportStatus;
use App\Exports\SimpleTableExport;
use App\Jobs\ProcessWarehouseImport;
use App\Models\Import;
use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Maatwebsite\Excel\Facades\Excel;

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
     * Import danh sách kho từ file Excel.
     *
     * @param UploadedFile $file
     * @return array{import_id:int,status:string}
     */
    public function importWarehouses(UploadedFile $file): array
    {
        $storedPath = $file->store('imports/warehouses', 'local');

        $import = Import::create([
            'type' => ImportType::WAREHOUSE,
            'status' => ImportStatus::PENDING,
            'file_path' => $storedPath,
            'created_by' => Auth::id(),
        ]);

        ProcessWarehouseImport::dispatch($import);

        return [
            'import_id' => $import->id,
            'status' => $import->status->value,
        ];
    }

    public function exportWarehouses(?array $ids = null): BinaryFileResponse
    {
        $query = Warehouse::query()
            ->with('parent:id,code,name');
        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        }
        $rows = $query
            ->orderBy('id')
            ->get()
            ->map(function (Warehouse $warehouse) {
                return [
                    $warehouse->code,
                    $warehouse->name,
                ];
            });
        $headings = [
            'Mã',
            'Tên',
        ];
        return Excel::download(
            new SimpleTableExport($rows, $headings),
            'FileKhoSach.xlsx'
        );
    }
}