<?php

namespace Tests\Feature\Backend;

use App\Models\DigitalDocumentSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DigitalDocumentSubmissionApiTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    private function mediaDisk(): string
    {
        return (string) config('filesystems.media_disk', 'public');
    }

    public function test_reader_lists_own_submissions_then_hide_removes_from_list_but_row_remains(): void
    {
        [$reader, $token] = $this->createUserAndToken([
            'email' => 'reader-dds@test.com',
        ]);

        $submission = DigitalDocumentSubmission::query()->create([
            'submitted_by' => $reader->id,
            'title' => 'Luận văn thử',
            'author_names' => 'Nguyễn Văn A',
            'description' => null,
            'file_path' => 'upload/digital-document-submissions/test.pdf',
            'original_name' => 'test.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 1200,
            'status' => DigitalDocumentSubmission::STATUS_PENDING,
        ]);

        $list = $this->getJson('/api/v1/me/digital-document-submissions', $this->apiTokenHeaders($token));
        $list->assertOk()->assertJsonPath('status', 'success');
        $this->assertCount(1, $list->json('data'));

        $hide = $this->postJson(
            "/api/v1/me/digital-document-submissions/{$submission->id}/hide",
            [],
            $this->apiTokenHeaders($token)
        );
        $hide->assertOk()->assertJsonPath('status', 'success');

        $list2 = $this->getJson('/api/v1/me/digital-document-submissions', $this->apiTokenHeaders($token));
        $this->assertCount(0, $list2->json('data'));

        $this->assertDatabaseHas('digital_document_submissions', [
            'id' => $submission->id,
            'status' => DigitalDocumentSubmission::STATUS_PENDING,
        ]);
        $this->assertNotNull(
            DigitalDocumentSubmission::query()->findOrFail($submission->id)->user_hidden_at
        );
    }

    public function test_staff_still_sees_hidden_submission_in_me_list(): void
    {
        [$reader] = $this->createUserAndToken(['email' => 'r2@test.com']);
        [, $librarianToken] = $this->createLibrarianUserAndToken();

        $submission = DigitalDocumentSubmission::query()->create([
            'submitted_by' => $reader->id,
            'title' => 'Tài liệu ẩn',
            'author_names' => 'B',
            'description' => null,
            'file_path' => 'upload/digital-document-submissions/h.pdf',
            'original_name' => 'h.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 800,
            'status' => DigitalDocumentSubmission::STATUS_PENDING,
            'user_hidden_at' => now(),
        ]);

        $list = $this->getJson('/api/v1/me/digital-document-submissions', $this->apiTokenHeaders($librarianToken));
        $list->assertOk();
        $ids = collect($list->json('data'))->pluck('id')->all();
        $this->assertContains($submission->id, $ids);
    }

    public function test_reader_can_submit_pdf_with_optional_cover_image(): void
    {
        Storage::fake($this->mediaDisk());

        [$reader, $token] = $this->createUserAndToken(['email' => 'reader-submit@test.com']);

        $pdf = UploadedFile::fake()->create('Vu-Tuan-Kiet-CV.pdf', 120, 'application/pdf');
        $cover = UploadedFile::fake()->image('uml-cover.png', 120, 160);

        $response = $this->post('/api/v1/me/digital-document-submissions', [
            'title' => 'Luận văn thử',
            'author_names' => 'kiet',
            'description' => 'mô tả ngắn',
            'file' => $pdf,
            'cover_image' => $cover,
        ], $this->apiTokenHeaders($token));

        $response->assertCreated()->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('digital_document_submissions', [
            'submitted_by' => $reader->id,
            'title' => 'Luận văn thử',
            'author_names' => 'kiet',
            'status' => DigitalDocumentSubmission::STATUS_PENDING,
        ]);

        $row = DigitalDocumentSubmission::query()->where('submitted_by', $reader->id)->latest('id')->first();
        $this->assertNotNull($row);
        $this->assertNotNull($row->cover_image_path);
        Storage::disk($this->mediaDisk())->assertExists($row->file_path);
        Storage::disk($this->mediaDisk())->assertExists($row->cover_image_path);
    }

    public function test_reader_can_submit_pdf_when_browser_sends_octet_stream_mime(): void
    {
        Storage::fake($this->mediaDisk());

        [$reader, $token] = $this->createUserAndToken(['email' => 'octet-pdf@test.com']);

        $pdf = UploadedFile::fake()->create('VŨ-TUẤN-KIỆT-CV.pdf', 200, 'application/octet-stream');

        $response = $this->post('/api/v1/me/digital-document-submissions', [
            'title' => 'CV',
            'author_names' => 'kiet',
            'description' => 'test',
            'file' => $pdf,
        ], $this->apiTokenHeaders($token));

        $response->assertCreated()->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('digital_document_submissions', [
            'submitted_by' => $reader->id,
            'title' => 'CV',
            'status' => DigitalDocumentSubmission::STATUS_PENDING,
        ]);
    }

    public function test_staff_cannot_submit_via_reader_endpoint_must_use_admin_catalog(): void
    {
        Storage::fake($this->mediaDisk());
        [, $librarianToken] = $this->createLibrarianUserAndToken();
        $pdf = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $response = $this->post('/api/v1/me/digital-document-submissions', [
            'title' => 'Thử',
            'author_names' => 'A',
            'description' => '',
            'file' => $pdf,
        ], $this->apiTokenHeaders($librarianToken));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['role']);
    }
}
