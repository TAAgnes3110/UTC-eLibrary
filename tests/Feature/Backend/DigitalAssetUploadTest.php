<?php

namespace Tests\Feature\Backend;

use App\Enums\ResourceType;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DigitalAssetUploadTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    public function test_admin_can_upload_pdf_when_browser_sends_octet_stream_mime(): void
    {
        Storage::fake('local');

        $book = Book::query()->create([
            'title' => 'Luận văn upload test',
            'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => 'online_only',
            'quantity' => 0,
        ]);

        [, $token] = $this->createAdminUserAndToken();

        $pdf = UploadedFile::fake()->create('do-an-luan-van.pdf', 200, 'application/octet-stream');

        $response = $this->post(
            "/api/v1/books/{$book->id}/digital-assets",
            [
                'file' => $pdf,
                'is_primary' => true,
                'visibility' => 'public',
            ],
            $this->apiTokenHeaders($token)
        );

        $response->assertCreated()->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('digital_assets', [
            'book_id' => $book->id,
            'original_name' => 'do-an-luan-van.pdf',
            'is_primary' => 1,
        ]);
    }

    public function test_admin_upload_rejects_non_pdf_extension(): void
    {
        Storage::fake('local');

        $book = Book::query()->create([
            'title' => 'Sách số',
            'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => 'online_only',
            'quantity' => 0,
        ]);

        [, $token] = $this->createAdminUserAndToken();

        $file = UploadedFile::fake()->create('shell.php', 10, 'application/x-php');

        $this->post(
            "/api/v1/books/{$book->id}/digital-assets",
            ['file' => $file],
            $this->apiTokenHeaders($token)
        )->assertStatus(422);
    }
}
