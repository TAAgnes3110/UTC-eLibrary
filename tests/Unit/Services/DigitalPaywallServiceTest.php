<?php

namespace Tests\Unit\Services;

use App\Models\DigitalAsset;
use App\Models\DigitalAssetPaywallSetting;
use App\Models\DigitalAssetPdfDownloadEntitlement;
use App\Services\DigitalPaywallService;
use App\Services\LibrarySettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit test DigitalPaywallService.
 * Kiểm tra logic quyền tải PDF, giá, và các edge cases.
 */
class DigitalPaywallServiceTest extends TestCase
{
    use RefreshDatabase;

    private DigitalPaywallService $service;

    private LibrarySettingsService $settingsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settingsService = Mockery::mock(LibrarySettingsService::class);
        $this->service = new DigitalPaywallService($this->settingsService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeAsset(array $attrs = []): DigitalAsset
    {
        $asset = new DigitalAsset($attrs);
        $asset->id = $attrs['id'] ?? 1;

        return $asset;
    }

    // ── resolvePdfDownloadPriceVnd ────────────────────────────────────────────

    #[Test]
    public function price_is_zero_when_paywall_disabled_on_asset(): void
    {
        $asset = $this->makeAsset();
        $setting = new DigitalAssetPaywallSetting;
        $setting->is_paywall_enabled = false;
        $setting->pdf_download_price_vnd = 50000;
        $asset->setRelation('paywallSetting', $setting);

        $price = $this->service->resolvePdfDownloadPriceVnd($asset);
        $this->assertEquals(0, $price);
    }

    #[Test]
    public function price_uses_asset_level_setting_when_set(): void
    {
        $asset = $this->makeAsset();
        $setting = new DigitalAssetPaywallSetting;
        $setting->is_paywall_enabled = true;
        $setting->pdf_download_price_vnd = 25000;
        $asset->setRelation('paywallSetting', $setting);

        $price = $this->service->resolvePdfDownloadPriceVnd($asset);
        $this->assertEquals(25000, $price);
    }

    #[Test]
    public function price_falls_back_to_library_default_when_no_asset_setting(): void
    {
        $asset = $this->makeAsset();
        $asset->setRelation('paywallSetting', null);

        $this->settingsService->shouldReceive('getDigitalDefaultPdfDownloadPriceVnd')
            ->once()
            ->andReturn(15000);

        $price = $this->service->resolvePdfDownloadPriceVnd($asset);
        $this->assertEquals(15000, $price);
    }

    #[Test]
    public function price_is_never_negative(): void
    {
        $asset = $this->makeAsset();
        $setting = new DigitalAssetPaywallSetting;
        $setting->is_paywall_enabled = true;
        $setting->pdf_download_price_vnd = -9999; // Âm
        $asset->setRelation('paywallSetting', $setting);

        $price = $this->service->resolvePdfDownloadPriceVnd($asset);
        $this->assertEquals(0, $price); // max(0, -9999)
    }

    #[Test]
    public function price_with_null_asset_price_falls_back_to_default(): void
    {
        $asset = $this->makeAsset();
        $setting = new DigitalAssetPaywallSetting;
        $setting->is_paywall_enabled = true;
        $setting->pdf_download_price_vnd = null; // Null → dùng default
        $asset->setRelation('paywallSetting', $setting);

        $this->settingsService->shouldReceive('getDigitalDefaultPdfDownloadPriceVnd')
            ->once()
            ->andReturn(8000);

        $price = $this->service->resolvePdfDownloadPriceVnd($asset);
        $this->assertEquals(8000, $price);
    }

    // ── isPaywallEnabled ─────────────────────────────────────────────────────

    #[Test]
    public function paywall_is_enabled_when_price_greater_than_zero(): void
    {
        $asset = $this->makeAsset();
        $setting = new DigitalAssetPaywallSetting;
        $setting->is_paywall_enabled = true;
        $setting->pdf_download_price_vnd = 10000;
        $asset->setRelation('paywallSetting', $setting);

        $this->assertTrue($this->service->isPaywallEnabled($asset));
    }

    #[Test]
    public function paywall_is_disabled_when_price_is_zero(): void
    {
        $asset = $this->makeAsset();
        $setting = new DigitalAssetPaywallSetting;
        $setting->is_paywall_enabled = false;
        $setting->pdf_download_price_vnd = 10000;
        $asset->setRelation('paywallSetting', $setting);

        $this->assertFalse($this->service->isPaywallEnabled($asset));
    }

    // ── userHasPdfDownloadEntitlement (DB integration) ───────────────────────

    #[Test]
    public function user_with_valid_entitlement_can_download(): void
    {
        $userId = 999;
        $assetId = 888;

        DigitalAssetPdfDownloadEntitlement::forceCreate([
            'user_id' => $userId,
            'digital_asset_id' => $assetId,
            'order_id' => null,
            'granted_at' => now()->subDay(),
            'expires_at' => null,
            'revoked_at' => null,
        ]);

        $result = $this->service->userHasPdfDownloadEntitlement($userId, $assetId);
        $this->assertTrue($result);
    }

    #[Test]
    public function user_without_entitlement_cannot_download(): void
    {
        $result = $this->service->userHasPdfDownloadEntitlement(999, 888);
        $this->assertFalse($result);
    }

    #[Test]
    public function revoked_entitlement_does_not_grant_access(): void
    {
        $userId = 777;
        $assetId = 666;

        DigitalAssetPdfDownloadEntitlement::forceCreate([
            'user_id' => $userId,
            'digital_asset_id' => $assetId,
            'order_id' => null,
            'granted_at' => now()->subDays(2),
            'expires_at' => null,
            'revoked_at' => now()->subDay(), // Đã bị thu hồi
        ]);

        $this->assertFalse($this->service->userHasPdfDownloadEntitlement($userId, $assetId));
    }

    #[Test]
    public function expired_entitlement_does_not_grant_access(): void
    {
        $userId = 555;
        $assetId = 444;

        DigitalAssetPdfDownloadEntitlement::forceCreate([
            'user_id' => $userId,
            'digital_asset_id' => $assetId,
            'order_id' => null,
            'granted_at' => now()->subDays(10),
            'expires_at' => now()->subDays(5), // Đã hết hạn
            'revoked_at' => null,
        ]);

        $this->assertFalse($this->service->userHasPdfDownloadEntitlement($userId, $assetId));
    }

    #[Test]
    public function entitlement_expiring_in_future_grants_access(): void
    {
        $userId = 333;
        $assetId = 222;

        DigitalAssetPdfDownloadEntitlement::forceCreate([
            'user_id' => $userId,
            'digital_asset_id' => $assetId,
            'order_id' => null,
            'granted_at' => now()->subDay(),
            'expires_at' => now()->addDays(30), // Chưa hết hạn
            'revoked_at' => null,
        ]);

        $this->assertTrue($this->service->userHasPdfDownloadEntitlement($userId, $assetId));
    }

    #[Test]
    public function entitlement_check_with_custom_now_respects_time(): void
    {
        $userId = 111;
        $assetId = 100;
        $expiresAt = Carbon::parse('2025-01-01');

        DigitalAssetPdfDownloadEntitlement::forceCreate([
            'user_id' => $userId,
            'digital_asset_id' => $assetId,
            'order_id' => null,
            'granted_at' => Carbon::parse('2024-01-01'),
            'expires_at' => $expiresAt,
            'revoked_at' => null,
        ]);

        // Kiểm tra tại thời điểm trước khi hết hạn
        $this->assertTrue(
            $this->service->userHasPdfDownloadEntitlement($userId, $assetId, Carbon::parse('2024-12-31'))
        );

        // Kiểm tra tại thời điểm sau khi hết hạn
        $this->assertFalse(
            $this->service->userHasPdfDownloadEntitlement($userId, $assetId, Carbon::parse('2025-01-02'))
        );
    }

    // ── userCanDownloadPdf ────────────────────────────────────────────────────

    #[Test]
    public function user_can_download_free_asset_without_entitlement(): void
    {
        $asset = $this->makeAsset(['id' => 50]);
        $setting = new DigitalAssetPaywallSetting;
        $setting->is_paywall_enabled = false;
        $setting->pdf_download_price_vnd = 0;
        $asset->setRelation('paywallSetting', $setting);

        $this->assertTrue($this->service->userCanDownloadPdf(999, $asset));
    }

    #[Test]
    public function user_without_entitlement_cannot_download_paid_asset(): void
    {
        $asset = $this->makeAsset(['id' => 60]);
        $setting = new DigitalAssetPaywallSetting;
        $setting->is_paywall_enabled = true;
        $setting->pdf_download_price_vnd = 20000;
        $asset->setRelation('paywallSetting', $setting);
        $asset->setRelation('book', null);

        $this->assertFalse($this->service->userCanDownloadPdf(999, $asset));
    }
}
