<?php

namespace Tests\Feature\Backend;

use App\Enums\ResourceType;
use App\Enums\UploadDirectory;
use App\Models\Book;
use App\Models\DigitalAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReaderDigitalPreviewPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_page_renders_with_pages_and_back_url(): void
    {
        $this->withoutVite();

        Storage::fake('local');

        $book = Book::query()->create([
            'title' => 'test2',
            'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => 'online_only',
            'quantity' => 0,
        ]);

        $asset = DigitalAsset::query()->create([
            'book_id' => $book->id,
            'version' => 1,
            'is_primary' => true,
            'storage_disk' => 'local',
            'path' => 'utc-elibrary/books/digital-assets/'.$book->id.'/source.pdf',
            'original_name' => '发音练习.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 100,
            'visibility' => 'internal',
        ]);

        $previewPath = UploadDirectory::digitalAssetPreview((int) $book->id, (int) $asset->id);
        $imagePath = UploadDirectory::digitalAssetPreviewPageImage((int) $book->id, (int) $asset->id, 1);
        Storage::disk('local')->put($previewPath, '%PDF-1.4 preview');
        Storage::disk('local')->put($imagePath, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        ));

        $asset->forceFill([
            'preview_path' => $previewPath,
            'preview_page_count' => 1,
            'preview_display' => ['pages' => [['page' => 1, 'path' => $imagePath]]],
            'preview_status' => 'ready',
        ])->save();

        $response = $this->get(route('reader.catalog.digital-preview', [
            'book' => $book->id,
            'digital_asset' => $asset->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reader/BookDigitalPreview')
            ->where('book.id', $book->id)
            ->where('asset.id', $asset->id)
            ->where('back_url', route('reader.catalog.show', ['book' => $book->id], false))
            ->has('pages', 1)
            ->where('pages.0.image_url', fn ($url) => str_contains($url, 'xem-truoc/trang/1.png'))
        );
    }

    public function test_guest_can_open_digital_book_show_with_preview_flag(): void
    {
        $this->withoutVite();

        Storage::fake('local');

        $book = Book::query()->create([
            'title' => 'Đồ án khách xem trước',
            'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => 'online_only',
            'quantity' => 0,
        ]);

        $asset = DigitalAsset::query()->create([
            'book_id' => $book->id,
            'version' => 1,
            'is_primary' => true,
            'storage_disk' => 'local',
            'path' => 'utc-elibrary/books/digital-assets/'.$book->id.'/source.pdf',
            'original_name' => 'do-an.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 100,
            'visibility' => 'internal',
        ]);

        $previewPath = UploadDirectory::digitalAssetPreview((int) $book->id, (int) $asset->id);
        $imagePath = UploadDirectory::digitalAssetPreviewPageImage((int) $book->id, (int) $asset->id, 1);
        Storage::disk('local')->put($previewPath, '%PDF-1.4 preview');
        Storage::disk('local')->put($imagePath, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        ));

        $asset->forceFill([
            'preview_path' => $previewPath,
            'preview_page_count' => 1,
            'preview_display' => ['pages' => [['page' => 1, 'path' => $imagePath]]],
            'preview_status' => 'ready',
        ])->save();

        $this->get(route('reader.catalog.show', ['book' => $book->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reader/BookShow')
                ->has('book.digital_assets', 1)
                ->where('book.digital_assets.0.preview_status', 'ready')
                ->where('book.digital_assets.0.preview_available', true)
                ->where('book.digital_assets.0.preview_url', fn ($url) => str_contains($url, 'xem-truoc')));
    }

    public function test_preview_page_shows_pending_message_when_not_ready(): void
    {
        $this->withoutVite();

        Storage::fake('local');

        $book = Book::query()->create([
            'title' => 'Chờ preview',
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
            'original_name' => 'do-an.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 100,
            'visibility' => 'internal',
            'preview_status' => 'pending',
        ]);

        $this->get(route('reader.catalog.digital-preview', [
            'book' => $book->id,
            'digital_asset' => $asset->id,
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reader/BookDigitalPreview')
                ->where('preview_state', 'pending')
                ->where('pages', [])
                ->where('preview_message', fn ($msg) => str_contains($msg, 'Đang tạo'))
            );
    }
}
