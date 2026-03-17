<?php

namespace App\Services;

use App\Enums\ImportStatus;
use App\Enums\ImportType;
use App\Models\ClassificationDetail;
use App\Exports\SimpleTableExport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use App\Jobs\ProcessClassificationDetailImport;
use App\Models\Import;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Maatwebsite\Excel\Facades\Excel;

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
        return ClassificationDetail::where('classification_id', $classificationId)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'classification_id']);
    }

    public function destroy(ClassificationDetail $detail): void
    {
        $detail->delete();
        MasterDataService::clearCache();
    }

    /**
     * Import danh sách phân loại chi tiết từ file Excel (chạy nền qua queue).
     *
     * @param UploadedFile $file
     * @return array{import_id:int,status:string}
     */
    public function importClassificationDetails(UploadedFile $file): array
    {
        $storedPath = $file->store('imports/classification_details', 'local');

        $import = Import::create([
            'type' => ImportType::CLASSIFICATION_DETAIL,
            'status' => ImportStatus::PENDING,
            'file_path' => $storedPath,
            'created_by' => Auth::id(),
        ]);

        ProcessClassificationDetailImport::dispatch($import);

        return [
            'import_id' => $import->id,
            'status' => $import->status->value,
        ];
    }

    /**
     * Xuất danh sách phân loại chi tiết ra file Excel.
     *
     * @param array<int,int>|null $ids
     */
    public function exportClassificationDetails(?array $ids = null): BinaryFileResponse
    {
        $query = ClassificationDetail::query()
            ->with('classification:id,code,name');
        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        }

        $rows = $query
            ->orderBy('classification_id')
            ->orderBy('code')
            ->get()
            ->map(function (ClassificationDetail $detail) {
                return [
                    $detail->id,
                    optional($detail->classification)->code,
                    $detail->code,
                    $detail->name,
                ];
            });

        $headings = [
            'ID',
            'Mã phân loại chính',
            'Mã phân loại chi tiết',
            'Tên phân loại chi tiết',
        ];

        return Excel::download(
            new SimpleTableExport($rows, $headings),
            'danh_sach_phan_loai_sach_chi_tiet.xlsx'
        );
    }
}
