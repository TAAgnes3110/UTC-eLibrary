<?php

namespace Tests\Feature\Backend;

use App\Models\NewsPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NewsPostApiTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    private function mediaDisk(): string
    {
        return (string) config('filesystems.media_disk', 'public');
    }

    public function test_admin_can_create_news_post_with_attachments(): void
    {
        Storage::fake($this->mediaDisk());
        [$admin, $adminToken] = $this->createAdminUserAndToken();

        $thumbnail = UploadedFile::fake()->image('thumb.webp', 400, 220);
        $image = UploadedFile::fake()->image('cover.png');
        $pdf = UploadedFile::fake()->create('notice.pdf', 200, 'application/pdf');

        $response = $this->post('/api/v1/news-posts', [
            'title' => 'Thông báo nghỉ lễ',
            'content' => 'Nội dung thông báo chi tiết',
            'status' => NewsPost::STATUS_ACTIVE,
            'thumbnail' => $thumbnail,
            'attachments' => [$image, $pdf],
        ], $this->apiTokenHeaders($adminToken));

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.posted_by.id', $admin->id);
        $this->assertDatabaseHas('news_posts', [
            'title' => 'Thông báo nghỉ lễ',
            'status' => NewsPost::STATUS_ACTIVE,
        ]);
        $post = NewsPost::query()->latest('id')->firstOrFail();
        $this->assertNotNull($post->published_at);
        $this->assertNotNull($post->thumbnail_path);
        $this->assertTrue(Storage::disk($this->mediaDisk())->exists((string) $post->thumbnail_path));
        $this->assertCount(2, $post->attachments);
        foreach ($post->attachments as $attachment) {
            $this->assertTrue(Storage::disk($this->mediaDisk())->exists($attachment->file_path));
        }
    }

    public function test_admin_can_update_news_post_and_remove_attachment(): void
    {
        Storage::fake($this->mediaDisk());
        [, $adminToken] = $this->createAdminUserAndToken();

        $create = $this->post('/api/v1/news-posts', [
            'title' => 'Bản nháp',
            'content' => 'Nội dung bản nháp',
            'status' => NewsPost::STATUS_INACTIVE,
            'attachments' => [UploadedFile::fake()->create('draft.pdf', 120, 'application/pdf')],
        ], $this->apiTokenHeaders($adminToken));
        $create->assertCreated();
        $postId = (int) $create->json('data.id');
        $attachmentId = (int) $create->json('data.attachments.0.id');
        $oldPath = (string) $create->json('data.attachments.0.file_path');

        $update = $this->put("/api/v1/news-posts/{$postId}", [
            'title' => 'Bản công bố',
            'content' => 'Nội dung đã cập nhật',
            'status' => NewsPost::STATUS_ACTIVE,
            'remove_attachment_ids' => [$attachmentId],
            'attachments' => [UploadedFile::fake()->create('new.pdf', 150, 'application/pdf')],
        ], $this->apiTokenHeaders($adminToken));

        $update->assertOk()->assertJsonPath('data.title', 'Bản công bố');
        $this->assertFalse(Storage::disk($this->mediaDisk())->exists($oldPath));
        $this->assertCount(1, (array) $update->json('data.attachments'));
    }

    public function test_public_endpoint_only_returns_active_news(): void
    {
        Storage::fake($this->mediaDisk());
        [, $adminToken] = $this->createAdminUserAndToken();

        $this->post('/api/v1/news-posts', [
            'title' => 'Tin công khai',
            'content' => 'Nội dung công khai',
            'status' => NewsPost::STATUS_ACTIVE,
            'thumbnail' => UploadedFile::fake()->image('public-thumb.png'),
        ], $this->apiTokenHeaders($adminToken))->assertCreated();

        $this->post('/api/v1/news-posts', [
            'title' => 'Tin nháp',
            'content' => 'Nội dung nháp',
            'status' => NewsPost::STATUS_INACTIVE,
        ], $this->apiTokenHeaders($adminToken))->assertCreated();

        $response = $this->getJson('/api/v1/news-posts/public?per_page=10');
        $response->assertOk()->assertJsonPath('status', 'success');

        $items = (array) $response->json('data.data');
        $this->assertCount(1, $items);
        $this->assertSame(NewsPost::STATUS_ACTIVE, (string) ($items[0]['status'] ?? ''));
        $this->assertNotEmpty((string) ($items[0]['thumbnail_path'] ?? ''));
        $this->assertNotEmpty((string) ($items[0]['thumbnail_url'] ?? ''));
        $this->assertNotEmpty((string) ($items[0]['slug'] ?? ''));
    }

    public function test_public_can_get_active_news_by_slug(): void
    {
        Storage::fake($this->mediaDisk());
        [, $adminToken] = $this->createAdminUserAndToken();

        $published = $this->post('/api/v1/news-posts', [
            'title' => 'Tin tức SEO công khai',
            'content' => 'Nội dung SEO',
            'status' => NewsPost::STATUS_ACTIVE,
        ], $this->apiTokenHeaders($adminToken));
        $published->assertCreated();
        $slug = (string) $published->json('data.slug');

        $draft = $this->post('/api/v1/news-posts', [
            'title' => 'Tin tức bản nháp riêng tư',
            'content' => 'Nội dung nháp',
            'status' => NewsPost::STATUS_INACTIVE,
        ], $this->apiTokenHeaders($adminToken));
        $draft->assertCreated();
        $draftSlug = (string) $draft->json('data.slug');

        $publicShow = $this->getJson("/api/v1/news-posts/{$slug}");
        $publicShow->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.slug', $slug);

        $draftShow = $this->getJson("/api/v1/news-posts/{$draftSlug}");
        $draftShow->assertStatus(410);
    }

    public function test_admin_delete_marks_post_inactive_and_hides_from_admin_list(): void
    {
        Storage::fake($this->mediaDisk());
        [, $adminToken] = $this->createAdminUserAndToken();

        $create = $this->post('/api/v1/news-posts', [
            'title' => 'Tin sẽ xoá',
            'content' => 'Nội dung',
            'attachments' => [UploadedFile::fake()->create('delete.pdf', 80, 'application/pdf')],
        ], $this->apiTokenHeaders($adminToken));
        $create->assertCreated();
        $postId = (int) $create->json('data.id');
        $delete = $this->deleteJson("/api/v1/news-posts/{$postId}", [], $this->apiTokenHeaders($adminToken));
        $delete->assertStatus(204);

        $this->assertDatabaseHas('news_posts', [
            'id' => $postId,
            'status' => NewsPost::STATUS_INACTIVE,
        ]);

        $index = $this->getJson('/api/v1/news-posts', $this->apiTokenHeaders($adminToken));
        $index->assertOk();
        $this->assertCount(0, (array) $index->json('data.data'));
    }

    public function test_admin_cannot_upload_more_than_ten_attachments(): void
    {
        Storage::fake($this->mediaDisk());
        [, $adminToken] = $this->createAdminUserAndToken();

        $attachments = [];
        for ($i = 1; $i <= 11; $i++) {
            $attachments[] = UploadedFile::fake()->create("file-{$i}.pdf", 20, 'application/pdf');
        }

        $response = $this->post('/api/v1/news-posts', [
            'title' => 'Tin nhiều tệp',
            'content' => 'Nội dung',
            'status' => NewsPost::STATUS_INACTIVE,
            'attachments' => $attachments,
        ], $this->apiTokenHeaders($adminToken));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['attachments']);
    }

    public function test_news_attachment_links_use_root_relative_urls(): void
    {
        config([
            'app.url' => 'http://localhost:8000',
            'filesystems.media_disk' => 'public',
        ]);
        Storage::fake('public');
        [, $adminToken] = $this->createAdminUserAndToken();

        $response = $this->post('/api/v1/news-posts', [
            'title' => 'Tin có đính kèm',
            'content' => 'Nội dung',
            'status' => NewsPost::STATUS_ACTIVE,
            'attachments' => [UploadedFile::fake()->create('notice.pdf', 200, 'application/pdf')],
        ], $this->apiTokenHeaders($adminToken));

        $response->assertCreated();
        $content = (string) $response->json('data.content');
        $this->assertStringNotContainsString('localhost:8000', $content);
        $this->assertStringContainsString('href="/utc-elibrary/', $content);
    }

    public function test_public_news_rewrites_legacy_localhost_attachment_links(): void
    {
        Storage::fake($this->mediaDisk());
        [, $adminToken] = $this->createAdminUserAndToken();

        $create = $this->post('/api/v1/news-posts', [
            'title' => 'Tin cũ có link localhost',
            'content' => 'Nội dung',
            'status' => NewsPost::STATUS_ACTIVE,
            'attachments' => [UploadedFile::fake()->create('legacy.pdf', 120, 'application/pdf')],
        ], $this->apiTokenHeaders($adminToken));
        $create->assertCreated();

        $post = NewsPost::query()->latest('id')->firstOrFail();
        $post->update([
            'content' => '<p>Nội dung</p><p><a href="http://localhost:8000/upload/news/attachments/legacy.pdf">legacy.pdf</a></p>',
        ]);

        $slug = (string) $post->slug;
        $publicShow = $this->getJson("/api/v1/news-posts/{$slug}");
        $publicShow->assertOk();

        $content = (string) $publicShow->json('data.content');
        $this->assertStringNotContainsString('localhost:8000', $content);
        $this->assertStringContainsString('href="/upload/news/attachments/legacy.pdf"', $content);
    }
}
