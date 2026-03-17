<?php

namespace App\Services;

use App\Enums\ImportStatus;
use App\Enums\ImportType;
use App\Models\Classification;
use App\Exports\SimpleTableExport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use App\Jobs\ProcessClassificationImport;
use App\Models\Import;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Maatwebsite\Excel\Facades\Excel;

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
        return Classification::orderBy('code')->get(['id', 'code', 'name']);
    }

    public function destroy(Classification $classification): void
    {
        $classification->delete();
        MasterDataService::clearCache();
    }

    /**
     * Import danh sách phân loại từ file Excel (chạy nền qua queue).
     *
     * @param UploadedFile $file
     * @return array{import_id:int,status:string}
     */
    public function importClassifications(UploadedFile $file): array
    {
        $storedPath = $file->store('imports/classifications', 'local');

        $import = Import::create([
            'type' => ImportType::CLASSIFICATION,
            'status' => ImportStatus::PENDING,
            'file_path' => $storedPath,
            'created_by' => Auth::id(),
        ]);

        ProcessClassificationImport::dispatch($import);

        return [
            'import_id' => $import->id,
            'status' => $import->status->value,
        ];
    }

    /**
     * Xuất danh sách phân loại ra file Excel.
     *
     * @param array<int,int>|null $ids
     */
    public function exportClassifications(?array $ids = null): BinaryFileResponse
    {
        $query = Classification::query();
        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        }
        $rows = $query
            ->with('parent:id,code,name')
            ->orderBy('code')
            ->get()
            ->map(function (Classification $classification) {
                return [
                    $classification->id,
                    $classification->code,
                    $classification->name,
                    optional($classification->parent)->code,
                    optional($classification->parent)->name,
                ];
            });

        $headings = [
            'ID',
            'Mã phân loại',
            'Tên phân loại',
            'Mã phân loại cha',
            'Tên phân loại cha',
        ];

        return Excel::download(
            new SimpleTableExport($rows, $headings),
            'danh_sach_phan_loai_sach.xlsx'
        );
    }
}
