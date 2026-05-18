<?php

namespace App\Jobs;

use App\Enums\DigitalAssetPreviewStatus;
use App\Models\DigitalAsset;
use App\Services\DigitalAssetPreviewDisplayService;
use App\Services\DigitalAssetPreviewService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/** Tạo preview.pdf + PNG hiển thị — chạy nền, không chặn upload/duyệt. */
class GenerateDigitalAssetPreviewJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 600;

    public int $uniqueFor = 3600;

    public function __construct(public int $digitalAssetId) {}

    public function uniqueId(): string
    {
        return 'digital-asset-preview:'.$this->digitalAssetId;
    }

    public function handle(
        DigitalAssetPreviewService $previewService,
        DigitalAssetPreviewDisplayService $displayService,
    ): void {
        $asset = DigitalAsset::query()->with('paywallSetting')->find($this->digitalAssetId);
        if ($asset === null) {
            return;
        }

        $previewService->markPreviewStatus($asset, DigitalAssetPreviewStatus::Processing);

        if ($previewService->paywallPreviewDisabled($asset)) {
            $previewService->markPreviewStatus($asset, DigitalAssetPreviewStatus::Disabled);
            $previewService->clearPreview($asset);

            return;
        }

        if ($previewService->hasPreview($asset) && ! $displayService->hasPreviewDisplay($asset)) {
            if ($displayService->ensureDisplayFromStoredPreview($asset->fresh())) {
                $previewService->markPreviewStatus($asset->fresh(), DigitalAssetPreviewStatus::Ready);

                return;
            }
        }

        $ok = $previewService->generate($asset->fresh());
        $asset->refresh();

        if ($ok && $displayService->hasPreviewDisplay($asset)) {
            $previewService->markPreviewStatus($asset, DigitalAssetPreviewStatus::Ready);

            return;
        }

        Log::warning('digital_asset.preview_job_failed', [
            'digital_asset_id' => $asset->id,
        ]);

        $previewService->markPreviewStatus($asset, DigitalAssetPreviewStatus::Failed);
    }
}
