<?php

namespace Tests\Feature\Public;

use App\Enums\ResourceType;
use App\Models\Book;
use App\Models\DigitalDocumentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * API công khai digital-document-submissions — không lộ PII / file gốc.
 */
class PublicDigitalSubmissionSecurityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function public_list_must_not_expose_submitter_email_or_raw_file_url(): void
    {
        Storage::fake('public');

        $submitter = User::factory()->create([
            'email' => 'submitter-secret@utc.edu.vn',
            'name' => 'Người nộp bí mật',
        ]);

        $book = Book::query()->create([
            'title' => 'Luận văn công khai',
            'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => 'online_only',
            'quantity' => 0,
        ]);

        DigitalDocumentSubmission::query()->create([
            'submitted_by' => $submitter->id,
            'title' => 'Đề tài A',
            'author_names' => 'Tác giả A',
            'file_path' => 'utc-elibrary/digital-submissions/files/secret.pdf',
            'original_name' => 'secret.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 1024,
            'status' => DigitalDocumentSubmission::STATUS_APPROVED,
            'approved_book_id' => $book->id,
        ]);

        $response = $this->getJson('/api/v1/digital-document-submissions')->assertSuccessful();

        $body = $response->getContent();
        $this->assertStringNotContainsString('submitter-secret@utc.edu.vn', $body);
        $this->assertStringNotContainsString('digital-submissions/files/secret.pdf', $body);

        $first = $response->json('data.data.0') ?? $response->json('data.0') ?? null;
        if (is_array($first)) {
            $this->assertArrayNotHasKey('file_url', $first);
            $this->assertArrayNotHasKey('file_path', $first);
            if (isset($first['submitter']) && is_array($first['submitter'])) {
                $this->assertArrayNotHasKey('email', $first['submitter']);
            }
        }
    }

    #[Test]
    public function public_list_rejects_per_page_over_50(): void
    {
        $this->getJson('/api/v1/digital-document-submissions?per_page=100')
            ->assertStatus(422);
    }

    #[Test]
    public function public_list_with_sql_injection_keyword_returns_200(): void
    {
        $this->getJson("/api/v1/digital-document-submissions?keyword=' OR 1=1--")
            ->assertSuccessful();
    }
}
