<?php

namespace Tests\Feature\Backend;

use App\Enums\ResourceType;
use App\Models\Book;
use App\Models\DigitalAsset;
use App\Services\DigitalAssetPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DeployProfileInfinityFreeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('deploy.profile', 'infinityfree');
        Config::set('deploy.is_infinityfree', true);
        Config::set('deploy.allow_shell_pdf_tools', false);
        Config::set('deploy.allow_imagick_pdf', false);
        Config::set('deploy.allow_runtime_preview_generation', false);
        Config::set('deploy.run_post_upload_processing_on_host', false);
        Config::set('deploy.max_digital_pdf_kilobytes', 20480);
    }

    public function test_reader_preview_unavailable_without_prebuilt_file_on_infinityfree(): void
    {
        Storage::fake('local');

        $book = Book::query()->create([
            'title' => 'Infinity PDF',
            'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => 'online_only',
            'quantity' => 0,
        ]);

        $sourcePath = 'utc-elibrary/books/digital-assets/'.$book->id.'/source.pdf';
        Storage::disk('local')->put($sourcePath, '%PDF-1.4 test');

        $asset = DigitalAsset::query()->create([
            'book_id' => $book->id,
            'version' => 1,
            'is_primary' => true,
            'storage_disk' => 'local',
            'path' => $sourcePath,
            'original_name' => 'source.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 100,
            'visibility' => 'internal',
        ]);

        $preview = app(DigitalAssetPreviewService::class);
        $this->assertFalse($preview->isPreviewAvailableForReader($asset));

        $response = $this->get(route('reader.catalog.digital-preview', [
            'book' => $book->id,
            'digital_asset' => $asset->id,
        ]));

        $response->assertNotFound();
    }

    public function test_reader_preview_serves_prebuilt_file_on_infinityfree(): void
    {
        $this->withoutVite();

        Storage::fake('local');

        $book = Book::query()->create([
            'title' => 'Has preview',
            'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => 'online_only',
            'quantity' => 0,
        ]);

        $previewPath = 'utc-elibrary/books/digital-assets/'.$book->id.'/1/preview.pdf';
        Storage::disk('local')->put($previewPath, '%PDF-1.4 preview');

        $asset = DigitalAsset::query()->create([
            'book_id' => $book->id,
            'version' => 1,
            'is_primary' => true,
            'storage_disk' => 'local',
            'path' => 'utc-elibrary/books/digital-assets/'.$book->id.'/source.pdf',
            'preview_path' => $previewPath,
            'preview_page_count' => 5,
            'original_name' => 'doc.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 100,
            'visibility' => 'internal',
        ]);

        $preview = app(DigitalAssetPreviewService::class);
        $this->assertTrue($preview->isPreviewAvailableForReader($asset));

        $imagePath = 'utc-elibrary/books/digital-assets/'.$book->id.'/1/preview-pages/1.png';
        Storage::disk('local')->put($imagePath, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        ));

        $asset->forceFill([
            'preview_display' => [
                'pages' => [['page' => 1, 'path' => $imagePath]],
            ],
        ])->save();

        $response = $this->get(route('reader.catalog.digital-preview', [
            'book' => $book->id,
            'digital_asset' => $asset->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reader/BookDigitalPreview')
            ->has('pages', 1)
        );
    }
}
