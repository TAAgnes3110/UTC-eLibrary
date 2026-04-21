<?php

namespace Database\Seeders;

use App\Models\Classification;
use App\Models\Warehouse;
use App\Services\BookshelfCellService;
use Illuminate\Database\Seeder;

class BookshelfSampleSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(BookshelfCellService::class);
        $warehouse = Warehouse::query()->where('is_active', true)->orderBy('id')->first();
        if (! ($warehouse instanceof Warehouse)) {
            return;
        }

        $classificationCount = Classification::query()->count();
        $maxRows = max(1, $classificationCount);
        $maxColumns = 20;

        $service->generateMatrix($warehouse, true, $maxRows, $maxColumns);
    }
}
