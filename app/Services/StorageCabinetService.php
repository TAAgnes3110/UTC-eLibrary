<?php

namespace App\Services;

use App\Models\Classification;
use App\Models\ClassificationDetail;
use App\Models\StorageCabinet;
use App\Models\StorageSlot;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class StorageCabinetService
{
    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));
        $withSlots = (bool) ($filters['with_slots'] ?? false);
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
            ->when($withSlots, fn ($q) => $q->with([
                'slots:id,storage_cabinet_id,classification_detail_id,slot_code,slot_name,capacity,current_quantity,is_active',
                'slots.classificationDetail:id,classification_id,code,name',
            ]))
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
            $query->where(function ($q) {
                $q->whereNull('current_quantity')->orWhere('current_quantity', '<=', 0);
            });
        } elseif ($status === 'full') {
            $query->whereRaw('capacity_total > 0 AND current_quantity >= capacity_total');
        }

        if ($sort === 'name_asc') {
            $query->orderBy('name');
        } elseif ($sort === 'name_desc') {
            $query->orderByDesc('name');
        } elseif ($sort === 'newest') {
            $query->orderByDesc('id');
        } elseif ($sort === 'oldest') {
            $query->orderBy('id');
        } elseif ($sort === 'stock_desc') {
            $query->orderByDesc('current_quantity')->orderByDesc('capacity_total');
        } elseif ($sort === 'stock_asc') {
            $query->orderBy('current_quantity')->orderBy('capacity_total');
        } elseif ($sort === 'usage_desc') {
            $query->orderByRaw('CASE WHEN capacity_total > 0 THEN current_quantity / capacity_total ELSE 0 END DESC');
        } elseif ($sort === 'usage_asc') {
            $query->orderByRaw('CASE WHEN capacity_total > 0 THEN current_quantity / capacity_total ELSE 0 END ASC');
        } else {
            $query->orderBy('warehouse_id')
                ->orderBy('classification_id')
                ->orderBy('name');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function paginateSlots(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $warehouseId = (int) ($filters['warehouse_id'] ?? 0);
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

        $query = StorageSlot::query()
            ->with([
                'classificationDetail:id,classification_id,code,name',
                'cabinet:id,warehouse_id,classification_id,code,name',
                'cabinet.warehouse:id,code,name',
            ])
            ->whereHas('cabinet')
            ->when($warehouseId > 0, fn (Builder $q) => $q->whereHas('cabinet', fn (Builder $cq) => $cq->where('warehouse_id', $warehouseId)))
            ->when($keyword !== '', function (Builder $q) use ($keyword, $searchIn) {
                $q->where(function (Builder $sub) use ($keyword, $searchIn) {
                    $applied = false;
                    if (in_array('name', $searchIn, true)) {
                        $sub->where('slot_name', 'like', "%{$keyword}%")
                            ->orWhereHas('cabinet', fn (Builder $cq) => $cq->where('name', 'like', "%{$keyword}%"));
                        $applied = true;
                    }
                    if (in_array('code', $searchIn, true)) {
                        $method = $applied ? 'orWhere' : 'where';
                        $sub->{$method}('slot_code', 'like', "%{$keyword}%");
                        $method = $applied ? 'orWhereHas' : 'whereHas';
                        $sub->{$method}('cabinet', fn (Builder $cq) => $cq->where('code', 'like', "%{$keyword}%"));
                        $applied = true;
                    }
                    if (in_array('classification', $searchIn, true)) {
                        $method = $applied ? 'orWhereHas' : 'whereHas';
                        $sub->{$method}('classificationDetail', fn (Builder $dq) => $dq
                            ->where('code', 'like', "%{$keyword}%")
                            ->orWhere('name', 'like', "%{$keyword}%"));
                        $applied = true;
                    }
                    if (in_array('warehouse', $searchIn, true)) {
                        $method = $applied ? 'orWhereHas' : 'whereHas';
                        $sub->{$method}('cabinet.warehouse', fn (Builder $wq) => $wq
                            ->where('code', 'like', "%{$keyword}%")
                            ->orWhere('name', 'like', "%{$keyword}%"));
                    }
                });
            });

        if ($sort === 'name_asc') {
            $query->orderBy('slot_name')->orderBy('id');
        } elseif ($sort === 'name_desc') {
            $query->orderByDesc('slot_name')->orderByDesc('id');
        } elseif ($sort === 'newest') {
            $query->orderByDesc('id');
        } elseif ($sort === 'oldest') {
            $query->orderBy('id');
        } else {
            $query->orderByDesc('id');
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

        $data['capacity_total'] = max(0, (int) ($data['capacity_total'] ?? 0));
        $data['current_quantity'] = max(0, (int) ($data['current_quantity'] ?? 0));
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['name'] = trim((string) ($data['name'] ?? ''));
        $data['code'] = $this->generateNextCabinetCode((int) $warehouse->id, (string) $warehouse->code);

        return StorageCabinet::query()->create($data)->fresh(['warehouse:id,code,name,params', 'classification:id,code,name']);
    }

    public function update(StorageCabinet $cabinet, array $data): StorageCabinet
    {
        if (array_key_exists('capacity_total', $data)) {
            $data['capacity_total'] = max(0, (int) $data['capacity_total']);
        }
        if (array_key_exists('current_quantity', $data)) {
            $data['current_quantity'] = max(0, (int) $data['current_quantity']);
        }
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

    public function createSlot(StorageCabinet $cabinet, array $data): StorageSlot
    {
        $detail = ClassificationDetail::query()->find((int) $data['classification_detail_id']);
        if (! $detail || (int) $detail->classification_id !== (int) $cabinet->classification_id) {
            throw ValidationException::withMessages([
                'classification_detail_id' => ['Phân loại chi tiết phải thuộc đúng phân loại của tủ lưu trữ.'],
            ]);
        }

        $capacity = max(1, (int) ($data['capacity'] ?? 30));
        $currentQuantity = max(0, (int) ($data['current_quantity'] ?? 0));
        if ($currentQuantity > $capacity) {
            throw ValidationException::withMessages([
                'current_quantity' => ['Số lượng hiện có không được vượt quá sức chứa của ngăn.'],
            ]);
        }

        $slot = $cabinet->slots()->create([
            'classification_detail_id' => (int) $data['classification_detail_id'],
            'slot_code' => $data['slot_code'] ?? null,
            'slot_name' => trim((string) ($data['slot_name'] ?? '')) !== ''
                ? trim((string) $data['slot_name'])
                : (string) $detail->name,
            'capacity' => $capacity,
            'current_quantity' => $currentQuantity,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'params' => $data['params'] ?? null,
        ]);

        $this->refreshCabinetStats($cabinet);

        return $slot->fresh(['classificationDetail:id,classification_id,code,name']);
    }

    public function updateSlot(StorageSlot $slot, array $data): StorageSlot
    {
        $cabinet = $slot->cabinet()->firstOrFail();
        $detail = null;
        if (array_key_exists('classification_detail_id', $data)) {
            $detail = ClassificationDetail::query()->find((int) $data['classification_detail_id']);
            if (! $detail || (int) $detail->classification_id !== (int) $cabinet->classification_id) {
                throw ValidationException::withMessages([
                    'classification_detail_id' => ['Phân loại chi tiết phải thuộc đúng phân loại của tủ lưu trữ.'],
                ]);
            }
        } else {
            $detail = ClassificationDetail::query()->find((int) $slot->classification_detail_id);
        }

        $capacity = array_key_exists('capacity', $data)
            ? max(1, (int) $data['capacity'])
            : max(1, (int) $slot->capacity);
        $currentQuantity = array_key_exists('current_quantity', $data)
            ? max(0, (int) $data['current_quantity'])
            : max(0, (int) $slot->current_quantity);
        if ($currentQuantity > $capacity) {
            throw ValidationException::withMessages([
                'current_quantity' => ['Số lượng hiện có không được vượt quá sức chứa của ngăn.'],
            ]);
        }
        $data['capacity'] = $capacity;
        $data['current_quantity'] = $currentQuantity;
        if (array_key_exists('is_active', $data)) {
            $data['is_active'] = (bool) $data['is_active'];
        }
        if (! $detail) {
            throw ValidationException::withMessages([
                'classification_detail_id' => ['Phân loại chi tiết không hợp lệ.'],
            ]);
        }
        if (array_key_exists('slot_name', $data)) {
            $data['slot_name'] = trim((string) $data['slot_name']);
            if ($data['slot_name'] === '') {
                $data['slot_name'] = (string) $detail->name;
            }
        }

        $slot->update($data);
        $this->refreshCabinetStats($cabinet);

        return $slot->fresh(['classificationDetail:id,classification_id,code,name']);
    }

    public function destroySlot(StorageSlot $slot): void
    {
        $cabinet = $slot->cabinet()->first();
        $slot->delete();
        if ($cabinet) {
            $this->refreshCabinetStats($cabinet);
        }
    }

    private function refreshCabinetStats(StorageCabinet $cabinet): void
    {
        $stats = $cabinet->slots()
            ->selectRaw('COALESCE(SUM(capacity),0) as capacity_total, COALESCE(SUM(current_quantity),0) as current_total')
            ->first();

        $cabinet->update([
            'capacity_total' => (int) ($stats?->capacity_total ?? 0),
            'current_quantity' => (int) ($stats?->current_total ?? 0),
        ]);
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
