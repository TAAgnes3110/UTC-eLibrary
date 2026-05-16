<?php

namespace Tests\Feature\Backend;

use App\Enums\ResourceType;
use App\Http\Resources\ReaderBookCardResource;
use App\Models\Book;
use App\Models\DigitalAsset;
use App\Models\DigitalAssetPaywallSetting;
use App\Models\DigitalDocumentSubmission;
use App\Models\User;
use App\Services\BookService;
use App\Services\DigitalAssetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DigitalAssetReaderStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_show_increments_digital_view_stats(): void
    {
        Storage::fake('public');

        $book = Book::query()->create([
            'title' => 'Luận văn test',
            'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => 'online_only',
            'quantity' => 0,
        ]);

        $path = 'digital/stats-test.pdf';
        Storage::disk('public')->put($path, '%PDF-1.4');

        $asset = DigitalAsset::query()->create([
            'book_id' => $book->id,
            'version' => 1,
            'is_primary' => true,
            'storage_disk' => 'public',
            'path' => $path,
            'original_name' => 'stats-test.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 100,
            'visibility' => 'public',
        ]);
        DigitalAsset::query()->whereKey($asset->id)->update([
            'view_count' => 2,
            'download_count' => 1,
        ]);

        DigitalAssetPaywallSetting::query()->create([
            'digital_asset_id' => $asset->id,
            'is_paywall_enabled' => false,
            'pdf_download_price_vnd' => 0,
        ]);

        $response = $this->get(route('reader.catalog.show', ['book' => $book->id]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reader/BookShow')
            ->where('digital_stats.access_sessions', 3)
            ->where('digital_stats.downloads', 1)
        );

        $asset->refresh();
        $this->assertSame(3, (int) $asset->view_count);
    }

    public function test_catalog_card_view_count_matches_detail_after_visit(): void
    {
        Storage::fake('public');

        $book = Book::query()->create([
            'title' => 'Đồng bộ lượt xem',
            'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => 'online_only',
            'quantity' => 0,
        ]);

        $path = 'digital/sync-views.pdf';
        Storage::disk('public')->put($path, '%PDF-1.4');

        $asset = DigitalAsset::query()->create([
            'book_id' => $book->id,
            'version' => 1,
            'is_primary' => true,
            'storage_disk' => 'public',
            'path' => $path,
            'original_name' => 'sync-views.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 100,
            'visibility' => 'public',
        ]);
        DigitalAsset::query()->whereKey($asset->id)->update(['view_count' => 10]);

        $this->get(route('reader.catalog.show', ['book' => $book->id]))->assertOk();

        $paginator = app(BookService::class)->readerCatalog(null, null, 12, null, null, null, 'newest');
        $row = $paginator->getCollection()->firstWhere('id', $book->id);
        $this->assertNotNull($row);

        $card = (new ReaderBookCardResource($row))->resolve();
        $this->assertSame(11, $card['view_count']);
        $this->assertSame(11, (int) $asset->fresh()->view_count);
    }

    public function test_each_catalog_show_request_increments_view_once(): void
    {
        Storage::fake('public');

        $book = Book::query()->create([
            'title' => 'Một request một lượt',
            'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => 'online_only',
            'quantity' => 0,
        ]);

        $path = 'digital/once.pdf';
        Storage::disk('public')->put($path, '%PDF-1.4');

        $asset = DigitalAsset::query()->create([
            'book_id' => $book->id,
            'version' => 1,
            'is_primary' => true,
            'storage_disk' => 'public',
            'path' => $path,
            'original_name' => 'once.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 100,
            'visibility' => 'public',
        ]);

        $this->get(route('reader.catalog.show', ['book' => $book->id]))->assertOk();
        $this->get(route('reader.catalog.show', ['book' => $book->id]))->assertOk();

        $this->assertSame(2, (int) $asset->fresh()->view_count);
    }

    public function test_web_download_increments_download_count(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $book = Book::query()->create([
            'title' => 'Tải PDF test',
            'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => 'online_only',
            'quantity' => 0,
        ]);

        $path = 'digital/download-stats.pdf';
        Storage::disk('public')->put($path, '%PDF-1.4');

        $asset = DigitalAsset::query()->create([
            'book_id' => $book->id,
            'version' => 1,
            'is_primary' => true,
            'storage_disk' => 'public',
            'path' => $path,
            'original_name' => 'download-stats.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 100,
            'visibility' => 'public',
            'download_count' => 0,
        ]);

        DigitalAssetPaywallSetting::query()->create([
            'digital_asset_id' => $asset->id,
            'is_paywall_enabled' => true,
            'pdf_download_price_vnd' => 0,
        ]);

        DigitalDocumentSubmission::query()->create([
            'submitted_by' => $user->id,
            'title' => 'Tải PDF test',
            'author_names' => 'A',
            'file_path' => 'upload/x.pdf',
            'original_name' => 'x.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 100,
            'status' => DigitalDocumentSubmission::STATUS_APPROVED,
            'approved_book_id' => $book->id,
        ]);

        $this->actingAs($user)->get(
            route('reader.catalog.digital-download-pdf', ['book' => $book->id, 'digital_asset' => $asset->id])
        )->assertOk();

        $this->assertSame(1, (int) $asset->fresh()->download_count);
    }

    public function test_reader_catalog_lists_aggregated_digital_view_count(): void
    {
        Storage::fake('public');

        $book = Book::query()->create([
            'title' => 'Hiển thị danh mục',
            'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => 'online_only',
            'quantity' => 0,
        ]);

        $path = 'digital/catalog-views.pdf';
        Storage::disk('public')->put($path, '%PDF-1.4');

        $asset = DigitalAsset::query()->create([
            'book_id' => $book->id,
            'version' => 1,
            'is_primary' => true,
            'storage_disk' => 'public',
            'path' => $path,
            'original_name' => 'catalog-views.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 100,
            'visibility' => 'public',
        ]);
        DigitalAsset::query()->whereKey($asset->id)->update(['view_count' => 4]);

        $paginator = app(BookService::class)->readerCatalog(null, null, 12, null, null, null, 'newest');
        $row = $paginator->getCollection()->firstWhere('id', $book->id);
        $this->assertNotNull($row);

        $card = (new ReaderBookCardResource($row))->resolve();
        $this->assertSame(4, $card['view_count']);
    }

    public function test_catalog_list_view_count_matches_sum_after_borrow_joins(): void
    {
        Storage::fake('public');

        $book = Book::query()->create([
            'title' => 'Subquery đồng bộ',
            'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => 'online_only',
            'quantity' => 0,
        ]);

        $path = 'digital/subquery.pdf';
        Storage::disk('public')->put($path, '%PDF-1.4');

        $assetA = DigitalAsset::query()->create([
            'book_id' => $book->id,
            'version' => 1,
            'is_primary' => true,
            'storage_disk' => 'public',
            'path' => $path,
            'original_name' => 'a.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 100,
            'visibility' => 'public',
        ]);
        $assetB = DigitalAsset::query()->create([
            'book_id' => $book->id,
            'version' => 2,
            'is_primary' => false,
            'storage_disk' => 'public',
            'path' => $path,
            'original_name' => 'b.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 100,
            'visibility' => 'public',
        ]);
        DigitalAsset::query()->whereKey($assetA->id)->update(['view_count' => 12]);
        DigitalAsset::query()->whereKey($assetB->id)->update(['view_count' => 16]);

        $paginator = app(BookService::class)->readerCatalog(null, null, 12, null, null, null, 'newest');
        $row = $paginator->getCollection()->firstWhere('id', $book->id);
        $this->assertNotNull($row);

        $expected = app(DigitalAssetService::class)->sumViewCountForBook((int) $book->id);
        $this->assertSame(28, $expected);

        $card = (new ReaderBookCardResource($row))->resolve();
        $this->assertSame(28, $card['view_count']);
    }
}
