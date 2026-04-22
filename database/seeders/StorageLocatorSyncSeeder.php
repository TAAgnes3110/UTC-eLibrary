<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\StorageCabinet;
use Illuminate\Database\Seeder;

class StorageLocatorSyncSeeder extends Seeder
{
    public function run(): void
    {
        $books = Book::query()
            ->whereNotNull('warehouse_id')
            ->whereNotNull('classification_id')
            ->whereNotNull('classification_detail_id')
            ->with('copies:id,book_id')
            ->orderBy('warehouse_id')
            ->orderBy('classification_id')
            ->orderBy('classification_detail_id')
            ->orderBy('id')
            ->get();

        foreach ($books as $book) {
            $cabinet = StorageCabinet::query()
                ->where('warehouse_id', $book->warehouse_id)
                ->where('classification_id', $book->classification_id)
                ->first();
            if (! $cabinet) {
                continue;
            }

            $slots = $cabinet->slots()
                ->where('classification_detail_id', $book->classification_detail_id)
                ->orderBy('slot_code')
                ->get(['id', 'slot_code', 'slot_name']);
            if ($slots->isEmpty()) {
                continue;
            }

            $primarySlot = $slots->first();
            $book->update([
                'cabinet' => $cabinet->code ?: $cabinet->name,
                'shelf' => $primarySlot?->slot_code ?: $primarySlot?->slot_name,
                'params' => array_merge((array) ($book->params ?? []), [
                    'storage_locator' => [
                        'storage_cabinet_id' => (int) $cabinet->id,
                        'storage_slot_ids' => $slots->pluck('id')->map(fn ($v) => (int) $v)->values()->all(),
                        'primary_slot_id' => (int) ($primarySlot?->id ?? 0),
                        'primary_slot_code' => $primarySlot?->slot_code,
                    ],
                ]),
            ]);

            $copies = $book->copies;
            if ($copies->isNotEmpty()) {
                $slotIds = $slots->pluck('id')->values();
                $slotCount = max(1, $slotIds->count());
                foreach ($copies->values() as $idx => $copy) {
                    /** @var BookCopy $copy */
                    $slotId = (int) $slotIds[$idx % $slotCount];
                    $copy->update([
                        'storage_slot_id' => $slotId,
                        'location' => ($cabinet->code ?: 'TU').'-'.($slots[$idx % $slotCount]->slot_code ?: 'NG'),
                    ]);
                }
            }
        }
    }
}
