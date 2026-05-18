<?php

use App\Enums\DigitalAssetPreviewStatus;
use App\Models\DigitalAsset;
use App\Services\DigitalAssetPreviewService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('digital_assets', function (Blueprint $table) {
            $table->string('preview_status', 20)
                ->default(DigitalAssetPreviewStatus::Pending->value)
                ->after('preview_generated_at')
                ->comment('pending|processing|ready|failed|disabled — tạo preview qua queue');
        });

        /** @var DigitalAssetPreviewService $previewService */
        $previewService = app(DigitalAssetPreviewService::class);

        DigitalAsset::query()
            ->select(['id', 'book_id', 'path', 'preview_path', 'preview_display', 'preview_status'])
            ->orderBy('id')
            ->chunkById(100, function ($assets) use ($previewService): void {
                foreach ($assets as $asset) {
                    $asset->forceFill([
                        'preview_status' => $previewService->inferStoredPreviewStatus($asset),
                    ])->saveQuietly();
                }
            });
    }

    public function down(): void
    {
        Schema::table('digital_assets', function (Blueprint $table) {
            $table->dropColumn('preview_status');
        });
    }
};
