<?php

namespace Tests\Feature\Digital;

use App\Enums\ResourceType;
use App\Enums\UploadDirectory;
use App\Models\Book;
use App\Models\DigitalAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Xem trước PDF — không được phục vụ trang ngoài preview_display / giới hạn paywall.
 */
class DigitalPreviewPageSecurityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function preview_page_image_beyond_allowed_pages_returns_404(): void
    {
        Storage::fake('local');

        $book = Book::query()->create([
            'title' => 'Sách preview limit',
            'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => 'online_only',
            'quantity' => 0,
        ]);

        $asset = DigitalAsset::query()->create([
            'book_id' => $book->id,
            'version' => 1,
            'is_primary' => true,
            'storage_disk' => 'local',
            'path' => 'digital/limit.pdf',
            'original_name' => 'limit.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 100,
            'visibility' => 'internal',
            'preview_path' => 'preview.pdf',
            'preview_page_count' => 2,
        ]);

        $asset->forceFill([
            'preview_display' => [
                'pages' => [
                    ['page' => 1, 'path' => UploadDirectory::digitalAssetPreviewPageImage($book->id, $asset->id, 1)],
                    ['page' => 2, 'path' => UploadDirectory::digitalAssetPreviewPageImage($book->id, $asset->id, 2)],
                ],
            ],
        ])->save();

        $page1 = UploadDirectory::digitalAssetPreviewPageImage($book->id, $asset->id, 1);
        $page6 = UploadDirectory::digitalAssetPreviewPageImage($book->id, $asset->id, 6);
        Storage::disk('local')->put($page1, 'png-bytes-page1');
        Storage::disk('local')->put($page6, 'png-bytes-page6-leak');

        $this->get(route('reader.catalog.digital-preview-page-image', [
            'book' => $book->id,
            'digital_asset' => $asset->id,
            'page' => 6,
        ]))->assertStatus(404, 'Trang 6 không nằm trong preview_display — không được trả ảnh dù file tồn tại trên disk.');

        $this->get(route('reader.catalog.digital-preview-page-image', [
            'book' => $book->id,
            'digital_asset' => $asset->id,
            'page' => 1,
        ]))->assertOk();
    }

    #[Test]
    public function preview_page_with_zero_page_number_returns_404(): void
    {
        Storage::fake('local');

        $book = Book::query()->create([
            'title' => 'Sách page zero',
            'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => 'online_only',
            'quantity' => 0,
        ]);

        $asset = DigitalAsset::query()->create([
            'book_id' => $book->id,
            'version' => 1,
            'is_primary' => true,
            'storage_disk' => 'local',
            'path' => 'digital/zero.pdf',
            'original_name' => 'zero.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 100,
            'visibility' => 'internal',
        ]);

        $this->get('/tra-cuu-sach/'.$book->id.'/tai-lieu/'.$asset->id.'/xem-truoc/trang/0.png')
            ->assertStatus(404);
    }
}
