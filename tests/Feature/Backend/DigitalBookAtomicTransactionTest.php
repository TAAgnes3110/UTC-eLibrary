<?php

namespace Tests\Feature\Backend;

use App\Enums\ResourceType;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DigitalBookAtomicTransactionTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    public function test_store_digital_creates_book_and_asset_together(): void
    {
        Storage::fake('local');

        [, $token] = $this->createAdminUserAndToken();

        $pdf = UploadedFile::fake()->create('luận-văn.pdf', 200, 'application/pdf');

        $response = $this->post(
            '/api/v1/books/digital',
            [
                'title' => 'Đồ án atomic test',
                'file' => $pdf,
                'is_primary' => true,
                'visibility' => 'public',
            ],
            $this->apiTokenHeaders($token)
        );

        $response->assertCreated()->assertJsonPath('status', 'success');

        $bookId = (int) $response->json('data.id');
        $this->assertGreaterThan(0, $bookId);

        $this->assertDatabaseHas('books', [
            'id' => $bookId,
            'title' => 'Đồ án atomic test',
            'resource_type' => ResourceType::DIGITAL->value,
        ]);

        $this->assertDatabaseHas('digital_assets', [
            'book_id' => $bookId,
            'original_name' => 'luận-văn.pdf',
        ]);
    }

    public function test_store_digital_rejects_invalid_pdf_and_does_not_create_book(): void
    {
        Storage::fake('local');

        $before = Book::query()->count();

        [, $token] = $this->createAdminUserAndToken();

        $file = UploadedFile::fake()->create('shell.php', 10, 'application/x-php');

        $this->post(
            '/api/v1/books/digital',
            [
                'title' => 'Không được tạo',
                'file' => $file,
            ],
            $this->apiTokenHeaders($token)
        )->assertStatus(422);

        $this->assertSame($before, Book::query()->count());
        $this->assertDatabaseMissing('books', ['title' => 'Không được tạo']);
    }
}
