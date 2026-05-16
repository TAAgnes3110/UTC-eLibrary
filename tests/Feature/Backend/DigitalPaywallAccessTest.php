<?php

namespace Tests\Feature\Backend;

use App\Enums\ResourceType;
use App\Models\Book;
use App\Models\DigitalAsset;
use App\Models\DigitalAssetPaywallSetting;
use App\Models\DigitalAssetPdfDownloadEntitlement;
use App\Models\DigitalDocumentSubmission;
use App\Services\DigitalPaywallService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DigitalPaywallAccessTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    public function test_approved_submitter_can_download_pdf_without_entitlement(): void
    {
        [$submitter] = $this->createUserAndToken(['email' => 'submitter@test.com']);
        [$other] = $this->createUserAndToken(['email' => 'other@test.com']);

        $book = Book::query()->create([
            'title' => 'Luận văn A',
            'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => 'online_only',
            'quantity' => 0,
        ]);

        $asset = DigitalAsset::query()->create([
            'book_id' => $book->id,
            'version' => 1,
            'is_primary' => true,
            'storage_disk' => 'public',
            'path' => 'digital/test.pdf',
            'original_name' => 'test.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 1000,
            'visibility' => 'public',
        ]);

        DigitalAssetPaywallSetting::query()->create([
            'digital_asset_id' => $asset->id,
            'is_paywall_enabled' => true,
            'pdf_download_price_vnd' => 50000,
        ]);

        DigitalDocumentSubmission::query()->create([
            'submitted_by' => $submitter->id,
            'title' => 'Luận văn A',
            'author_names' => 'A',
            'file_path' => 'upload/x.pdf',
            'original_name' => 'x.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 1000,
            'status' => DigitalDocumentSubmission::STATUS_APPROVED,
            'approved_book_id' => $book->id,
        ]);

        $paywall = app(DigitalPaywallService::class);

        $this->assertTrue($paywall->userIsApprovedSubmitterOfAsset((int) $submitter->id, $asset));
        $this->assertTrue($paywall->userCanDownloadPdf((int) $submitter->id, $asset));
        $this->assertFalse($paywall->userCanDownloadPdf((int) $other->id, $asset));
    }

    public function test_purchased_user_can_download_pdf(): void
    {
        [$buyer] = $this->createUserAndToken(['email' => 'buyer@test.com']);

        $book = Book::query()->create([
            'title' => 'Sách B',
            'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => 'online_only',
            'quantity' => 0,
        ]);

        $asset = DigitalAsset::query()->create([
            'book_id' => $book->id,
            'version' => 1,
            'is_primary' => true,
            'storage_disk' => 'public',
            'path' => 'digital/b.pdf',
            'original_name' => 'b.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 1000,
            'visibility' => 'public',
        ]);

        DigitalAssetPaywallSetting::query()->create([
            'digital_asset_id' => $asset->id,
            'is_paywall_enabled' => true,
            'pdf_download_price_vnd' => 30000,
        ]);

        DigitalAssetPdfDownloadEntitlement::query()->create([
            'user_id' => $buyer->id,
            'digital_asset_id' => $asset->id,
        ]);

        $paywall = app(DigitalPaywallService::class);

        $this->assertTrue($paywall->userCanDownloadPdf((int) $buyer->id, $asset));
        $this->assertFalse($paywall->userIsApprovedSubmitterOfAsset((int) $buyer->id, $asset));
    }

    public function test_web_download_pdf_route_with_session_auth(): void
    {
        [$submitter] = $this->createUserAndToken(['email' => 'stream@test.com']);

        $book = Book::query()->create([
            'title' => 'Stream test',
            'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => 'online_only',
            'quantity' => 0,
        ]);

        $path = 'digital/stream-test.pdf';
        Storage::disk('public')->put($path, '%PDF-1.4 test');

        $asset = DigitalAsset::query()->create([
            'book_id' => $book->id,
            'version' => 1,
            'is_primary' => true,
            'storage_disk' => 'public',
            'path' => $path,
            'original_name' => 'stream-test.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 100,
            'visibility' => 'public',
        ]);

        DigitalAssetPaywallSetting::query()->create([
            'digital_asset_id' => $asset->id,
            'is_paywall_enabled' => true,
            'pdf_download_price_vnd' => 50000,
        ]);

        DigitalDocumentSubmission::query()->create([
            'submitted_by' => $submitter->id,
            'title' => 'Stream test',
            'author_names' => 'A',
            'file_path' => 'upload/x.pdf',
            'original_name' => 'x.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 100,
            'status' => DigitalDocumentSubmission::STATUS_APPROVED,
            'approved_book_id' => $book->id,
        ]);

        $response = $this->actingAs($submitter)->get(
            "/tra-cuu-sach/{$book->id}/tai-lieu/{$asset->id}/tai-pdf"
        );

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $body = $response->streamedContent();
        $this->assertStringStartsWith('%PDF', $body);
        $this->assertGreaterThan(10, strlen($body));
        $this->assertSame((string) strlen($body), $response->headers->get('Content-Length'));
    }
}
