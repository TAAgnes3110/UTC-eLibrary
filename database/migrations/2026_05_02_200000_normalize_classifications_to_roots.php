<?php

declare(strict_types=1);

use App\Models\Book;
use App\Models\Classification;
use App\Models\StorageCabinet;
use Illuminate\Database\Migrations\Migration;

/**
 * Chuẩn hóa: không còn bản phân loại có parent_id — gộp sách/tủ về CLS gốc rồi xóa bản con.
 * Không thể khôi phục cây con trong {@see down}.
 */
return new class extends Migration
{
    public function up(): void
    {
        $fallbackId = Classification::query()->roots()->where('code', '000')->value('id')
            ?? Classification::query()->roots()->orderBy('id')->value('id');

        while (Classification::query()->whereNotNull('parent_id')->exists()) {
            foreach (Classification::query()->whereNotNull('parent_id')->cursor() as $child) {
                /** @var Classification $child */
                $parent = Classification::query()->find((int) $child->parent_id);
                $targetId = $parent?->id ?? $fallbackId;

                Book::query()->where('classification_id', $child->id)->update(
                    $targetId !== null
                        ? [
                            'classification_id' => $targetId,
                            'cabinet' => Classification::query()->whereKey($targetId)->value('code'),
                        ]
                        : [
                            'classification_id' => null,
                            'cabinet' => null,
                        ]
                );

                StorageCabinet::query()->where('classification_id', $child->id)->update([
                    'classification_id' => $targetId,
                ]);

                $child->delete();
            }
        }
    }

    public function down(): void
    {
        // intentionally empty
    }
};
