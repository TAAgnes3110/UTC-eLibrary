<?php

namespace App\Services;

use App\Models\Classification;
use App\Models\StorageCabinet;
use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class StorageCabinetService
{
    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $warehouseId = (int) ($filters['warehouse_id'] ?? 0);
        $classificationId = (int) ($filters['classification_id'] ?? 0);
        $status = (string) ($filters['status'] ?? '');
        $sort = (string) ($filters['sort'] ?? '');
        $searchInRaw = $filters['search_in'] ?? null;
        $searchIn = is_array($searchInRaw)
            ? $searchInRaw
            : array_filter(array_map('trim', explode(',', (string) $searchInRaw)));
        $allowedSearchIn = ['code', 'name', 'warehouse', 'classification'];
        $searchIn = array_values(array_intersect($searchIn, $allowedSearchIn));
        if ($searchIn === []) {
            $searchIn = $allowedSearchIn;
        }

        $query = StorageCabinet::query()
            ->with([
                'warehouse:id,code,name,params',
                'classification:id,code,name',
            ])
            ->when($warehouseId > 0, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when($classificationId > 0, fn ($q) => $q->where('classification_id', $classificationId))
            ->when($keyword !== '', function ($q) use ($keyword, $searchIn) {
                $q->where(function ($sub) use ($keyword, $searchIn) {
                    $applied = false;
                    if (in_array('name', $searchIn, true)) {
                        $sub->where('name', 'like', "%{$keyword}%");
                        $applied = true;
                    }
                    if (in_array('code', $searchIn, true)) {
                        $method = $applied ? 'orWhere' : 'where';
                        $sub->{$method}('code', 'like', "%{$keyword}%");
                        $applied = true;
                    }
                    if (in_array('classification', $searchIn, true)) {
                        $method = $applied ? 'orWhereHas' : 'whereHas';
                        $sub->{$method}('classification', fn ($cq) => $cq
                            ->where('code', 'like', "%{$keyword}%")
                            ->orWhere('name', 'like', "%{$keyword}%"));
                        $applied = true;
                    }
                    if (in_array('warehouse', $searchIn, true)) {
                        $method = $applied ? 'orWhereHas' : 'whereHas';
                        $sub->{$method}('warehouse', fn ($wq) => $wq
                            ->where('code', 'like', "%{$keyword}%")
                            ->orWhere('name', 'like', "%{$keyword}%"));
                    }
                });
            });

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        } elseif ($status === 'empty') {
            $query->where('current_quantity', '<=', 0);
        } elseif ($status === 'full') {
            // Không còn giới hạn sức chứa, giữ no-op để tương thích filter cũ.
        }

        if ($sort === 'name_asc') {
            $query->orderBy('name');
        } elseif ($sort === 'name_desc') {
            $query->orderByDesc('name');
        } elseif ($sort === 'newest') {
            $query->orderByDesc('id');
        } elseif ($sort === 'oldest') {
            $query->orderBy('id');
        } elseif (in_array($sort, ['stock_desc', 'stock_asc'], true)) {
            $query->orderBy('warehouse_id')
                ->orderBy('classification_id')
                ->orderBy('name');
        } else {
            $query->orderBy('warehouse_id')
                ->orderBy('classification_id')
                ->orderBy('name');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function create(array $data): StorageCabinet
    {
        $classification = Classification::query()->find((int) ($data['classification_id'] ?? 0));
        if (! $classification) {
            throw ValidationException::withMessages([
                'classification_id' => ['Phân loại không hợp lệ.'],
            ]);
        }
        $warehouse = Warehouse::query()->find((int) ($data['warehouse_id'] ?? 0));
        if (! $warehouse) {
            throw ValidationException::withMessages([
                'warehouse_id' => ['Kho không hợp lệ.'],
            ]);
        }

        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['name'] = trim((string) ($data['name'] ?? ''));
        $data['code'] = $this->generateNextCabinetCode((int) $warehouse->id, (string) $warehouse->code);

        return StorageCabinet::query()->create($data)->fresh(['warehouse:id,code,name,params', 'classification:id,code,name']);
    }

    public function update(StorageCabinet $cabinet, array $data): StorageCabinet
    {
        if (array_key_exists('is_active', $data)) {
            $data['is_active'] = (bool) $data['is_active'];
        }
        unset($data['code']);
        if (array_key_exists('name', $data)) {
            $data['name'] = trim((string) $data['name']);
        }

        $cabinet->update($data);

        return $cabinet->fresh(['warehouse:id,code,name,params', 'classification:id,code,name']);
    }

    public function destroy(StorageCabinet $cabinet): void
    {
        $cabinet->delete();
    }

    private function generateNextCabinetCode(int $warehouseId, string $warehouseCode): string
    {
        $shortWarehouseCode = strtoupper(str_replace('KHO-', '', trim($warehouseCode)));
        $prefix = 'TU-'.($shortWarehouseCode !== '' ? $shortWarehouseCode : 'WH').'-';
        $existingCodes = StorageCabinet::query()
            ->withTrashed()
            ->where('warehouse_id', $warehouseId)
            ->pluck('code')
            ->filter()
            ->values();

        $max = 0;
        foreach ($existingCodes as $code) {
            $codeStr = (string) $code;
            if (! str_starts_with($codeStr, $prefix)) {
                continue;
            }
            $numberPart = substr($codeStr, strlen($prefix));
            if (! ctype_digit($numberPart)) {
                continue;
            }
            $max = max($max, (int) $numberPart);
        }

        return sprintf('%s%02d', $prefix, $max + 1);
    }
}
