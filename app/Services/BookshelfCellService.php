<?php

namespace App\Services;

use App\Exports\BookshelfCellExport;
use App\Models\BookshelfCell;
use App\Models\Classification;
use App\Models\ClassificationDetail;
use App\Models\Book;
use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookshelfCellService
{
    private const DEFAULT_BOOKS_PER_RACK = 30;

    /**
     * @param  array{
     *   keyword?: string|null,
     *   status?: string|null,
     *   sort?: string|null
     * }  $filters
     */
    public function paginate(?int $warehouseId, int $perPage, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $status = (string) ($filters['status'] ?? '');
        $sort = (string) ($filters['sort'] ?? '');

        $query = BookshelfCell::query()
            ->with([
                'warehouse:id,code,name',
                'classification:id,code,name',
                'classificationDetail:id,classification_id,code,name',
            ]);

        if ($warehouseId && $warehouseId > 0) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($keyword !== '') {
            $kw = mb_strtolower($keyword);
            $query->where(function ($q) use ($kw) {
                $q->whereRaw('LOWER(COALESCE(label, "")) like ?', ["%{$kw}%"])
                    ->orWhereRaw('LOWER(CONCAT("r", LPAD(row_index, 2, "0"), "-c", LPAD(column_index, 2, "0"))) like ?', ["%{$kw}%"])
                    ->orWhereHas('classification', function ($cq) use ($kw) {
                        $cq->whereRaw('LOWER(COALESCE(name, "")) like ?', ["%{$kw}%"])
                            ->orWhereRaw('LOWER(COALESCE(code, "")) like ?', ["%{$kw}%"]);
                    })
                    ->orWhereHas('classificationDetail', function ($dq) use ($kw) {
                        $dq->whereRaw('LOWER(COALESCE(name, "")) like ?', ["%{$kw}%"])
                            ->orWhereRaw('LOWER(COALESCE(code, "")) like ?', ["%{$kw}%"]);
                    })
                    ->orWhereHas('warehouse', function ($wq) use ($kw) {
                        $wq->whereRaw('LOWER(COALESCE(name, "")) like ?', ["%{$kw}%"])
                            ->orWhereRaw('LOWER(COALESCE(code, "")) like ?', ["%{$kw}%"]);
                    });
            });
        }

        if ($status === 'in_stock') {
            $query->where('current_quantity', '>', 0);
        } elseif ($status === 'out_of_stock') {
            $query->where(function ($q) {
                $q->whereNull('current_quantity')->orWhere('current_quantity', '<=', 0);
            });
        }

        if ($sort === 'label_asc') {
            $query->orderBy('label');
        } elseif ($sort === 'label_desc') {
            $query->orderByDesc('label');
        } elseif ($sort === 'newest') {
            $query->orderByDesc('id');
        } elseif ($sort === 'oldest') {
            $query->orderBy('id');
        } else {
            $query->orderBy('row_index')->orderBy('column_index');
        }

        $paginator = $query->paginate($perPage)->withQueryString();
        $paginator->getCollection()->transform(function (BookshelfCell $cell) {
            $cellQuantity = (int) ($cell->current_quantity ?? 0);
            $cell->book_stats = [
                'title_count' => 0,
                'quantity_total' => $cellQuantity,
                'available_title_count' => $cellQuantity > 0 ? 1 : 0,
                'has_stock' => $cellQuantity > 0,
            ];

            return $cell;
        });

        return $paginator;
    }

    public function overviewStats(?int $warehouseId = null): array
    {
        $base = BookshelfCell::query();
        if ($warehouseId && $warehouseId > 0) {
            $base->where('warehouse_id', $warehouseId);
        }

        $totalShelves = (clone $base)->count();
        $usedShelves = (clone $base)->where('current_quantity', '>', 0)->count();

        return [
            'usedShelves' => (int) $usedShelves,
            'emptyShelves' => (int) max(0, $totalShelves - $usedShelves),
            'totalShelves' => (int) $totalShelves,
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, BookshelfCell>
     */
    public function listByWarehouse(Warehouse $warehouse)
    {
        $cells = BookshelfCell::query()
            ->where('warehouse_id', $warehouse->id)
            ->with([
                'warehouse:id,code,name',
                'classification:id,code,name',
                'classificationDetail:id,classification_id,code,name',
            ])
            ->orderBy('row_index')
            ->orderBy('column_index')
            ->get();

        foreach ($cells as $cell) {
            $cellQuantity = (int) ($cell->current_quantity ?? 0);
            $cell->book_stats = [
                'title_count' => 0,
                'quantity_total' => $cellQuantity,
                'available_title_count' => $cellQuantity > 0 ? 1 : 0,
                'has_stock' => $cellQuantity > 0,
            ];
        }

        return $cells;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, BookshelfCell>
     */
    public function listAll(): \Illuminate\Database\Eloquent\Collection
    {
        $cells = BookshelfCell::query()
            ->with([
                'warehouse:id,code,name',
                'classification:id,code,name',
                'classificationDetail:id,classification_id,code,name',
            ])
            ->orderBy('warehouse_id')
            ->orderBy('row_index')
            ->orderBy('column_index')
            ->get();

        foreach ($cells as $cell) {
            $cellQuantity = (int) ($cell->current_quantity ?? 0);
            $cell->book_stats = [
                'title_count' => 0,
                'quantity_total' => $cellQuantity,
                'available_title_count' => $cellQuantity > 0 ? 1 : 0,
                'has_stock' => $cellQuantity > 0,
            ];
        }

        return $cells;
    }

    /**
     * Tạo dữ liệu mẫu theo rule:
     * - Mỗi phân loại = 1 hàng
     * - Mỗi phân loại chi tiết chiếm tối thiểu 1 kệ
     * - Mỗi kệ chứa ~30 bản; nếu vượt 30 thì tách thêm kệ cùng hàng
     */
    public function generateMatrix(Warehouse $warehouse, bool $reset = false, ?int $maxRows = null, ?int $maxColumns = null): array
    {
        return DB::transaction(function () use ($warehouse, $reset, $maxRows, $maxColumns) {
            $maxShelvesPerWarehouse = 50;
            if ($reset) {
                BookshelfCell::query()
                    ->where('warehouse_id', $warehouse->id)
                    ->forceDelete();
            }

            $maxRows = $maxRows ?? 20;
            $maxColumns = $maxColumns ?? 20;

            $quantityByDetail = Book::query()
                ->where('warehouse_id', $warehouse->id)
                ->selectRaw('classification_id, classification_detail_id, COALESCE(SUM(quantity),0) as total_qty')
                ->groupBy('classification_id', 'classification_detail_id')
                ->get()
                ->keyBy(fn ($row) => ((int) $row->classification_id).':'.((int) $row->classification_detail_id));

            $classifications = Classification::query()
                ->orderBy('code')
                ->limit($maxRows)
                ->get(['id', 'code', 'name']);

            $created = 0;
            foreach ($classifications as $rowIndex => $classification) {
                if ($created >= $maxShelvesPerWarehouse) {
                    break;
                }
                $details = ClassificationDetail::query()
                    ->where('classification_id', $classification->id)
                    ->orderBy('code')
                    ->limit($maxColumns)
                    ->get(['id', 'classification_id', 'code', 'name']);

                $columnIndex = 1;
                foreach ($details as $detail) {
                    if ($created >= $maxShelvesPerWarehouse) {
                        break;
                    }
                    if ($columnIndex > $maxColumns) {
                        break;
                    }
                    $qtyKey = ((int) $classification->id).':'.((int) $detail->id);
                    $totalQty = (int) ($quantityByDetail->get($qtyKey)?->total_qty ?? 0);
                    $rackCountForDetail = max(1, (int) ceil($totalQty / self::DEFAULT_BOOKS_PER_RACK));

                    for ($slot = 1; $slot <= $rackCountForDetail; $slot++) {
                        if ($created >= $maxShelvesPerWarehouse) {
                            break;
                        }
                        if ($columnIndex > $maxColumns) {
                            break;
                        }
                        $slotQuantity = max(0, min(self::DEFAULT_BOOKS_PER_RACK, $totalQty - (($slot - 1) * self::DEFAULT_BOOKS_PER_RACK)));
                        $label = sprintf('R%02d-C%02d', $rowIndex + 1, $columnIndex);
                        BookshelfCell::query()->updateOrCreate(
                            [
                                'warehouse_id' => $warehouse->id,
                                'row_index' => $rowIndex + 1,
                                'column_index' => $columnIndex,
                            ],
                            [
                                'label' => $label,
                                'current_quantity' => $slotQuantity,
                                'classification_id' => $classification->id,
                                'classification_detail_id' => $detail->id,
                                'is_active' => true,
                                'params' => [
                                    'auto_generated' => true,
                                    'row_code' => $classification->code,
                                    'column_code' => $detail->code,
                                    'books_per_rack' => self::DEFAULT_BOOKS_PER_RACK,
                                    'detail_total_qty' => $totalQty,
                                    'detail_slot_index' => $slot,
                                    'detail_slot_count' => $rackCountForDetail,
                                ],
                            ]
                        );
                        $created++;
                        $columnIndex++;
                    }
                }
            }

            return [
                'created_or_updated' => $created,
                'warehouse_id' => $warehouse->id,
            ];
        });
    }

    public function update(BookshelfCell $cell, array $data): BookshelfCell
    {
        if (! empty($data['classification_detail_id'])) {
            $detail = \App\Models\ClassificationDetail::query()->find((int) $data['classification_detail_id']);
            if ($detail && ! empty($data['classification_id']) && (int) $data['classification_id'] !== (int) $detail->classification_id) {
                throw ValidationException::withMessages([
                    'classification_detail_id' => ['Phân loại chi tiết không thuộc phân loại chính đã chọn.'],
                ]);
            }
            if ($detail && empty($data['classification_id'])) {
                $data['classification_id'] = $detail->classification_id;
            }
        }

        $cell->update($data);

        return $cell->fresh([
            'warehouse:id,code,name',
            'classification:id,code,name',
            'classificationDetail:id,classification_id,code,name',
        ]);
    }

    public function createAutoPlacement(array $data): BookshelfCell
    {
        $warehouseId = (int) ($data['warehouse_id'] ?? 0);
        $classificationId = (int) ($data['classification_id'] ?? 0);
        $classificationDetailId = (int) ($data['classification_detail_id'] ?? 0);

        $warehouse = Warehouse::query()->find($warehouseId);
        if (! $warehouse) {
            throw ValidationException::withMessages([
                'warehouse_id' => ['Kho sách không tồn tại.'],
            ]);
        }
        $classification = Classification::query()->find($classificationId);
        if (! $classification) {
            throw ValidationException::withMessages([
                'classification_id' => ['Phân loại không tồn tại.'],
            ]);
        }
        $detail = ClassificationDetail::query()->find($classificationDetailId);
        if (! $detail) {
            throw ValidationException::withMessages([
                'classification_detail_id' => ['Phân loại chi tiết không tồn tại.'],
            ]);
        }
        if ((int) $detail->classification_id !== $classification->id) {
            throw ValidationException::withMessages([
                'classification_detail_id' => ['Phân loại chi tiết không thuộc phân loại đã chọn.'],
            ]);
        }

        return DB::transaction(function () use ($warehouse, $classification, $detail, $data) {
            $requestedRow = isset($data['row_index']) ? (int) $data['row_index'] : null;
            $requestedColumn = isset($data['column_index']) ? (int) $data['column_index'] : null;
            $maxShelvesPerWarehouse = 50;
            $defaultColumns = 20;
            $maxRowsByShelfLimit = (int) ceil($maxShelvesPerWarehouse / $defaultColumns);

            $rowIndex = null;
            $columnIndex = null;
            $nextLinear = null;

            if ($requestedRow && $requestedColumn) {
                $requestedLinear = (($requestedRow - 1) * $defaultColumns) + $requestedColumn;

                if (
                    $requestedRow < 1
                    || $requestedRow > $maxRowsByShelfLimit
                    || $requestedColumn < 1
                    || $requestedColumn > $defaultColumns
                    || $requestedLinear > $maxShelvesPerWarehouse
                ) {
                    throw ValidationException::withMessages([
                        'row_index' => ['Vị trí kệ phải nằm trong giới hạn 50 kệ/kho.'],
                    ]);
                }
                $occupied = BookshelfCell::withTrashed()
                    ->where('warehouse_id', $warehouse->id)
                    ->where('row_index', $requestedRow)
                    ->where('column_index', $requestedColumn)
                    ->exists();
                if ($occupied) {
                    throw ValidationException::withMessages([
                        'row_index' => ['Vị trí kệ đã được sử dụng. Vui lòng chọn vị trí trống khác.'],
                    ]);
                }

                $rowIndex = $requestedRow;
                $columnIndex = $requestedColumn;
                $nextLinear = (($rowIndex - 1) * $defaultColumns) + $columnIndex;
            } else {
                $lastCell = BookshelfCell::withTrashed()
                    ->where('warehouse_id', $warehouse->id)
                    ->orderByDesc('row_index')
                    ->orderByDesc('column_index')
                    ->first();

                $lastLinear = 0;
                if ($lastCell) {
                    $lastLinear = (($lastCell->row_index - 1) * $defaultColumns) + $lastCell->column_index;
                }
                $nextLinear = $lastLinear + 1;
                if ($nextLinear > $maxShelvesPerWarehouse) {
                    throw ValidationException::withMessages([
                        'warehouse_id' => ['Kho đã đạt giới hạn 50 kệ.'],
                    ]);
                }
                $rowIndex = intdiv($nextLinear - 1, $defaultColumns) + 1;
                $columnIndex = (($nextLinear - 1) % $defaultColumns) + 1;
            }

            $label = trim((string) ($data['label'] ?? ''));
            if ($label === '') {
                throw ValidationException::withMessages([
                    'label' => ['Nhãn không được để trống.'],
                ]);
            }

            $cell = BookshelfCell::query()->create([
                'warehouse_id' => $warehouse->id,
                'row_index' => $rowIndex,
                'column_index' => $columnIndex,
                'label' => $label,
                'current_quantity' => 0,
                'classification_id' => $classification->id,
                'classification_detail_id' => $detail->id,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'params' => array_merge((array) ($data['params'] ?? []), [
                    'auto_generated' => true,
                    'position_linear' => $nextLinear,
                ]),
            ]);

            return $cell->fresh([
                'warehouse:id,code,name',
                'classification:id,code,name',
                'classificationDetail:id,classification_id,code,name',
            ]);
        });
    }

    public function destroy(BookshelfCell $cell): void
    {
        $cell->delete();
    }

    public function exportCells(?array $ids = null): StreamedResponse
    {
        return BookshelfCellExport::stream($ids);
    }
}
