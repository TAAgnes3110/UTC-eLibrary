<?php

namespace App\Support;

use App\Jobs\GenerateDigitalAssetPreviewJob;

/** Chọn queue nền hoặc chạy đồng bộ (local không có worker). */
final class DigitalAssetPreviewJobDispatcher
{
    public static function dispatch(int $digitalAssetId): void
    {
        if (self::shouldDispatchSynchronously()) {
            GenerateDigitalAssetPreviewJob::dispatchSync($digitalAssetId);

            return;
        }

        GenerateDigitalAssetPreviewJob::dispatch($digitalAssetId);
    }

    public static function shouldDispatchSynchronously(): bool
    {
        if (config('deploy.prefer_sync_queue')) {
            return true;
        }

        if (config('queue.default') === 'sync') {
            return true;
        }

        return (bool) config('deploy.preview_dispatch_sync', false);
    }
}
