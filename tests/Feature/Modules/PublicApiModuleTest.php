<?php

namespace Tests\Feature\Modules;

use App\Enums\ResourceType;
use App\Models\Book;
use App\Models\DigitalDocumentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Module: API công khai & health (10 case).
 */
class PublicApiModuleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function case01_health_returns_200_with_structure(): void
    {
        $this->getJson('/api/health')
            ->assertStatus(200)
            ->assertJsonStructure(['status', 'checks', 'timestamp']);
    }

    #[Test]
    public function case02_health_no_auth_required(): void
    {
        $this->getJson('/api/health')->assertSuccessful();
    }

    #[Test]
    public function case03_public_news_list_ok(): void
    {
        $this->getJson('/api/v1/news-posts/public')->assertSuccessful();
    }

    #[Test]
    public function case04_invalid_news_slug_returns_404(): void
    {
        $this->assertContains(
            $this->getJson('/api/v1/news-posts/INVALID-SLUG-UPPER')->status(),
            [404, 405]
        );
    }

    #[Test]
    public function case05_public_digital_submissions_ok(): void
    {
        $this->getJson('/api/v1/digital-document-submissions')->assertSuccessful();
    }

    #[Test]
    public function case06_public_submissions_must_not_leak_submitter_email(): void
    {
        $u = User::factory()->create(['email' => 'leak-public@utc.edu.vn']);
        $book = Book::query()->create([
            'title' => 'LV', 'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => 'online_only', 'quantity' => 0,
        ]);
        DigitalDocumentSubmission::query()->create([
            'submitted_by' => $u->id, 'title' => 'T', 'author_names' => 'A',
            'file_path' => 'secret/path.pdf', 'original_name' => 'x.pdf',
            'mime' => 'application/pdf', 'byte_size' => 1,
            'status' => DigitalDocumentSubmission::STATUS_APPROVED,
            'approved_book_id' => $book->id,
        ]);
        $body = $this->getJson('/api/v1/digital-document-submissions')->getContent();
        $this->assertStringNotContainsString('leak-public@utc.edu.vn', $body);
    }

    #[Test]
    public function case07_master_data_requires_auth(): void
    {
        $this->getJson('/api/v1/master-data')->assertStatus(401);
    }

    #[Test]
    public function case08_get_login_returns_405(): void
    {
        $this->getJson('/api/v1/auth/login')->assertStatus(405);
    }

    #[Test]
    public function case09_unknown_api_route_returns_404(): void
    {
        $this->getJson('/api/v1/does-not-exist-xyz')->assertStatus(404);
    }

    #[Test]
    public function case10_public_submissions_per_page_over_50_returns_422(): void
    {
        $this->getJson('/api/v1/digital-document-submissions?per_page=99')->assertStatus(422);
    }
}
