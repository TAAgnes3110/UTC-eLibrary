<?php

namespace Tests\Feature\Backend;

use App\Enums\ResourceType;
use App\Models\Book;
use App\Models\DigitalAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReaderCatalogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_inertia_includes_category_label_and_view_count(): void
    {
        $book = Book::query()->create([
            'title' => 'Đồ án kiểm tra danh mục',
            'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => 'online_only',
            'quantity' => 0,
        ]);

        $asset = DigitalAsset::query()->create([
            'book_id' => $book->id,
            'version' => 1,
            'is_primary' => true,
            'storage_disk' => 'local',
            'path' => 'utc-elibrary/test.pdf',
            'original_name' => 'test.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 100,
            'visibility' => 'internal',
        ]);
        DigitalAsset::query()->whereKey($asset->id)->update(['view_count' => 7]);

        $response = $this->get(route('reader.catalog'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reader/Catalog')
            ->has('books.data', fn (Assert $books) => $books
                ->where('0.id', $book->id)
                ->where('0.category_label', 'Đồ án, luận văn')
                ->where('0.view_count', 7)
                ->etc()
            )
        );
    }
}
