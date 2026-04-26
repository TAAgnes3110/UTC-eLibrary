<?php

namespace App\Services;

use App\Enums\BookStatus;
use Illuminate\Support\Facades\DB;

class StorageQuantitySyncService
{
    public function syncCabinetById(?int $cabinetId): void
    {
        if (! $cabinetId || $cabinetId <= 0) {
            return;
        }

        $row = DB::table('storage_cabinets as c')
            ->leftJoin('books as b', function ($join) {
                $join->on('b.warehouse_id', '=', 'c.warehouse_id')
                    ->on('b.classification_id', '=', 'c.classification_id')
                    ->whereNull('b.deleted_at');
            })
            ->leftJoin('book_copies as bc', function ($join) {
                $join->on('bc.book_id', '=', 'b.id')
                    ->where('bc.status', '=', BookStatus::AVAILABLE->value)
                    ->whereNull('bc.deleted_at');
            })
            ->where('c.id', $cabinetId)
            ->whereNull('c.deleted_at')
            ->groupBy('c.id')
            ->selectRaw('COUNT(bc.id) as current_quantity')
            ->first();

        DB::table('storage_cabinets')
            ->where('id', $cabinetId)
            ->update(['current_quantity' => max(0, (int) ($row->current_quantity ?? 0))]);
    }

    public function syncAll(): void
    {
        $rows = DB::table('storage_cabinets as c')
            ->leftJoin('books as b', function ($join) {
                $join->on('b.warehouse_id', '=', 'c.warehouse_id')
                    ->on('b.classification_id', '=', 'c.classification_id')
                    ->whereNull('b.deleted_at');
            })
            ->leftJoin('book_copies as bc', function ($join) {
                $join->on('bc.book_id', '=', 'b.id')
                    ->where('bc.status', '=', BookStatus::AVAILABLE->value)
                    ->whereNull('bc.deleted_at');
            })
            ->whereNull('c.deleted_at')
            ->groupBy('c.id')
            ->selectRaw('c.id as cabinet_id, COUNT(bc.id) as current_quantity')
            ->get();

        foreach ($rows as $row) {
            DB::table('storage_cabinets')
                ->where('id', (int) $row->cabinet_id)
                ->update(['current_quantity' => max(0, (int) $row->current_quantity)]);
        }
    }
}
