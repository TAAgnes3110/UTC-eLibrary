<?php

namespace Tests\Feature\Backend;

use App\Enums\ResourceType;
use App\Models\Book;
use App\Models\DigitalAsset;
use App\Models\DigitalAssetPaywallSetting;
use App\Services\DigitalAssetPreviewService;
use App\Services\DigitalPaywallService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class DigitalAssetPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_preview_pdf_with_configured_page_count(): void
    {
        Storage::fake('local');

        $book = Book::query()->create([
            'title' => 'Test digital',
            'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => 'online_only',
            'quantity' => 0,
        ]);

        $sourcePath = 'utc-elibrary/books/digital-assets/'.$book->id.'/source.pdf';
        Storage::disk('local')->put($sourcePath, $this->buildPdfBytes(8));

        $asset = DigitalAsset::query()->create([
            'book_id' => $book->id,
            'version' => 1,
            'is_primary' => true,
            'storage_disk' => 'local',
            'path' => $sourcePath,
            'original_name' => 'source.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 1000,
            'visibility' => 'internal',
        ]);

        DigitalAssetPaywallSetting::query()->create([
            'digital_asset_id' => $asset->id,
            'is_paywall_enabled' => true,
            'pdf_download_price_vnd' => 10000,
        ]);

        $service = app(DigitalAssetPreviewService::class);
        $this->assertTrue($service->generate($asset));
        $asset->refresh();

        $this->assertSame('ready', $asset->preview_status);
        $this->assertNotNull($asset->preview_path);
        $this->assertSame(5, (int) $asset->preview_page_count);
        Storage::disk('local')->assertExists($asset->preview_path);

        $previewPages = (new Fpdi)->setSourceFile(Storage::disk('local')->path($asset->preview_path));
        $this->assertSame(5, $previewPages);
    }

    public function test_short_pdf_preview_includes_all_pages_when_fewer_than_five(): void
    {
        Storage::fake('local');

        $book = Book::query()->create([
            'title' => 'Short PDF',
            'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => 'online_only',
            'quantity' => 0,
        ]);

        $sourcePath = 'utc-elibrary/books/digital-assets/'.$book->id.'/short.pdf';
        Storage::disk('local')->put($sourcePath, $this->buildPdfBytes(2));

        $asset = DigitalAsset::query()->create([
            'book_id' => $book->id,
            'version' => 1,
            'is_primary' => true,
            'storage_disk' => 'local',
            'path' => $sourcePath,
            'original_name' => 'short.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 200,
            'visibility' => 'internal',
        ]);

        DigitalAssetPaywallSetting::query()->create([
            'digital_asset_id' => $asset->id,
            'is_paywall_enabled' => true,
            'pdf_download_price_vnd' => 10000,
        ]);

        $service = app(DigitalAssetPreviewService::class);
        $this->assertSame(5, app(DigitalPaywallService::class)->resolvePreviewMaxPages($asset));
        $this->assertTrue($service->generate($asset));
        $asset->refresh();

        $this->assertSame(2, (int) $asset->preview_page_count);
    }

    public function test_preview_page_image_route_returns_png(): void
    {
        Storage::fake('local');

        $book = Book::query()->create([
            'title' => 'Preview route',
            'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => 'online_only',
            'quantity' => 0,
        ]);

        $imagePath = 'utc-elibrary/books/digital-assets/'.$book->id.'/1/preview-pages/1.png';
        Storage::disk('local')->put($imagePath, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        ));

        $asset = DigitalAsset::query()->create([
            'book_id' => $book->id,
            'version' => 1,
            'is_primary' => true,
            'storage_disk' => 'local',
            'path' => 'utc-elibrary/books/digital-assets/'.$book->id.'/source.pdf',
            'preview_path' => 'utc-elibrary/books/digital-assets/'.$book->id.'/1/preview.pdf',
            'preview_page_count' => 1,
            'preview_display' => [
                'pages' => [['page' => 1, 'path' => $imagePath]],
            ],
            'original_name' => 'source.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 500,
            'visibility' => 'internal',
        ]);

        DigitalAssetPaywallSetting::query()->create([
            'digital_asset_id' => $asset->id,
            'is_paywall_enabled' => true,
            'pdf_download_price_vnd' => 5000,
        ]);

        $response = $this->get(route('reader.catalog.digital-preview-page-image', [
            'book' => $book->id,
            'digital_asset' => $asset->id,
            'page' => 1,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'image/png');
    }

    private function buildPdfBytes(int $pages): string
    {
        $pdf = new Fpdi;
        for ($i = 1; $i <= max(1, $pages); $i++) {
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', '', 12);
            $pdf->Cell(0, 10, 'Page '.$i);
        }
        $tmp = tempnam(sys_get_temp_dir(), 'utc_test_').'.pdf';
        $pdf->Output('F', $tmp);
        $bytes = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $bytes;
    }
}
