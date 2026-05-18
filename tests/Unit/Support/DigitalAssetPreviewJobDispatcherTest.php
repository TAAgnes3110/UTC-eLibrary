<?php

namespace Tests\Unit\Support;

use App\Support\DigitalAssetPreviewJobDispatcher;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DigitalAssetPreviewJobDispatcherTest extends TestCase
{
    public function test_local_profile_defaults_to_sync_dispatch(): void
    {
        Config::set('deploy.prefer_sync_queue', false);
        Config::set('deploy.preview_dispatch_sync', true);
        Config::set('queue.default', 'redis');

        $this->assertTrue(DigitalAssetPreviewJobDispatcher::shouldDispatchSynchronously());
    }

    public function test_vps_with_redis_queue_uses_async_when_sync_disabled(): void
    {
        Config::set('deploy.prefer_sync_queue', false);
        Config::set('deploy.preview_dispatch_sync', false);
        Config::set('queue.default', 'redis');

        $this->assertFalse(DigitalAssetPreviewJobDispatcher::shouldDispatchSynchronously());
    }
}
