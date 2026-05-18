<?php

namespace Tests\Feature\Backend;

use App\Enums\NotificationType;
use App\Helpers\FileHelpers;
use App\Models\DigitalDocumentSubmission;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DigitalDocumentSubmissionNotificationTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    private function mediaDisk(): string
    {
        return (string) config('filesystems.media_disk', 'public');
    }

    public function test_reader_submit_notifies_staff_pending_digest(): void
    {
        Storage::fake($this->mediaDisk());

        [$reader, $readerToken] = $this->createUserAndToken(['email' => 'dds-notify-reader@test.com']);
        [$librarian] = $this->createLibrarianUserAndToken(['email' => 'dds-notify-lib@test.com', 'is_active' => true]);

        $pdf = UploadedFile::fake()->create('luận-văn.pdf', 120, 'application/pdf');

        $this->post('/api/v1/me/digital-document-submissions', [
            'title' => 'Luận văn thông báo',
            'author_names' => 'Nguyễn Văn A',
            'description' => 'Mô tả',
            'file' => $pdf,
        ], $this->apiTokenHeaders($readerToken))
            ->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'recipient_type' => Notification::RECIPIENT_ADMIN,
            'recipient_id' => $librarian->id,
            'type' => NotificationType::ADMIN_DIGITAL_DOCUMENT_SUBMISSION_PENDING->value,
        ]);

        $notification = Notification::query()
            ->where('recipient_id', $librarian->id)
            ->where('type', NotificationType::ADMIN_DIGITAL_DOCUMENT_SUBMISSION_PENDING->value)
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('1', $notification->message);
        $this->assertSame('/admin/books/digital/submissions', $notification->action_url);
    }

    public function test_staff_approve_notifies_submitter(): void
    {
        Storage::fake($this->mediaDisk());
        Storage::fake(FileHelpers::digitalAssetsDisk());

        [$reader, $readerToken] = $this->createUserAndToken(['email' => 'dds-approve-reader@test.com']);
        [, $librarianToken] = $this->createLibrarianUserAndToken();

        $pdf = UploadedFile::fake()->create('do-an.pdf', 150, 'application/pdf');

        $this->post('/api/v1/me/digital-document-submissions', [
            'title' => 'Đồ án duyệt',
            'author_names' => 'Tác giả',
            'file' => $pdf,
        ], $this->apiTokenHeaders($readerToken))
            ->assertCreated();

        $submission = DigitalDocumentSubmission::query()->where('submitted_by', $reader->id)->firstOrFail();

        $this->postJson(
            "/api/v1/digital-document-submissions/{$submission->id}/approve",
            ['review_note' => 'Hợp lệ'],
            $this->apiTokenHeaders($librarianToken)
        )->assertOk();

        $this->assertDatabaseHas('notifications', [
            'recipient_type' => Notification::RECIPIENT_USER,
            'recipient_id' => $reader->id,
            'type' => NotificationType::USER_DIGITAL_DOCUMENT_SUBMISSION_APPROVED->value,
            'entity_type' => DigitalDocumentSubmission::class,
            'entity_id' => $submission->id,
        ]);

        $notification = Notification::query()
            ->where('recipient_id', $reader->id)
            ->where('type', NotificationType::USER_DIGITAL_DOCUMENT_SUBMISSION_APPROVED->value)
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('Đồ án duyệt', $notification->message);
        $submission->refresh();
        $this->assertStringContainsString(
            '/tra-cuu-sach/'.$submission->approved_book_id,
            (string) $notification->action_url
        );
    }

    public function test_staff_reject_notifies_submitter_with_note(): void
    {
        Storage::fake($this->mediaDisk());

        [$reader] = $this->createUserAndToken(['email' => 'dds-reject-reader@test.com']);
        [, $librarianToken] = $this->createLibrarianUserAndToken();

        $submission = DigitalDocumentSubmission::query()->create([
            'submitted_by' => $reader->id,
            'title' => 'Luận văn từ chối',
            'author_names' => 'B',
            'description' => null,
            'file_path' => 'utc-elibrary/digital-submissions/files/reject.pdf',
            'original_name' => 'reject.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 800,
            'status' => DigitalDocumentSubmission::STATUS_PENDING,
        ]);
        Storage::disk($this->mediaDisk())->put($submission->file_path, '%PDF-1.4');

        $this->postJson(
            "/api/v1/digital-document-submissions/{$submission->id}/reject",
            ['review_note' => 'File không đúng định dạng nội bộ'],
            $this->apiTokenHeaders($librarianToken)
        )->assertOk();

        $this->assertDatabaseHas('notifications', [
            'recipient_type' => Notification::RECIPIENT_USER,
            'recipient_id' => $reader->id,
            'type' => NotificationType::USER_DIGITAL_DOCUMENT_SUBMISSION_REJECTED->value,
        ]);

        $notification = Notification::query()
            ->where('recipient_id', $reader->id)
            ->where('type', NotificationType::USER_DIGITAL_DOCUMENT_SUBMISSION_REJECTED->value)
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('File không đúng định dạng nội bộ', $notification->message);
        $this->assertSame('/dich-vu/tai-lieu-so', $notification->action_url);
    }
}
