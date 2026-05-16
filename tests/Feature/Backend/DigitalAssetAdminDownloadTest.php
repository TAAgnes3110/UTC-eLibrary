<?php

namespace Tests\Feature\Backend;

use App\Enums\ResourceType;
use App\Enums\RoleType;
use App\Models\Book;
use App\Models\DigitalAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DigitalAssetAdminDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_librarian_can_download_digital_asset_pdf_via_api(): void
    {
        Storage::fake('local');

        $librarian = User::factory()->create(['user_type' => RoleType::LIBRARIAN]);
        $book = Book::query()->create([
            'title' => 'Đồ án test',
            'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => 'online_only',
            'quantity' => 0,
        ]);

        $path = 'utc-elibrary/books/digital-assets/'.$book->id.'/sample.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 test');

        $asset = DigitalAsset::query()->create([
            'book_id' => $book->id,
            'version' => 1,
            'is_primary' => true,
            'storage_disk' => 'local',
            'path' => $path,
            'original_name' => 'do-an-mau.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 100,
            'visibility' => 'internal',
        ]);

        $response = $this->actingAs($librarian)
            ->get(route('admin.books.digital-assets.download', [
                'book' => $book->id,
                'digital_asset' => $asset->id,
            ]));

        $pdfBytes = '%PDF-1.4 test';

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('do-an-mau.pdf', (string) $response->headers->get('content-disposition'));
        $this->assertSame((string) strlen($pdfBytes), (string) $response->headers->get('content-length'));
    }
}
