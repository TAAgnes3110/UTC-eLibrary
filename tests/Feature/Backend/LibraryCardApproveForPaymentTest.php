<?php

namespace Tests\Feature\Backend;

use App\Enums\LibraryCardStatus;
use App\Enums\RoleType;
use App\Mail\LibraryCardPickupReminderMail;
use App\Models\Faculty;
use App\Models\LibraryCard;
use App\Models\Period;
use App\Models\User;
use App\Services\LibraryCard\LibraryCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class LibraryCardApproveForPaymentTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    public function test_librarian_approve_activates_card_and_sends_pickup_reminder_mail(): void
    {
        Mail::fake();

        $faculty = Faculty::query()->create(['code' => 'FA', 'name' => 'A', 'is_active' => true]);
        $period = Period::query()->create(['code' => 'PA', 'name' => 'P', 'is_active' => true]);
        $reader = User::factory()->create([
            'user_type' => RoleType::STUDENT,
            'avatar' => 'avatars/x.jpg',
            'email' => 'reader-approve@example.com',
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'L1',
        ]);

        $card = app(LibraryCardService::class)->createForUserHaveAccount($reader, [
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'L1',
        ]);

        [$librarian, $token] = $this->createLibrarianUserAndToken();

        $response = $this->postJson(
            "/api/v1/library-cards/{$card->id}/approve-review",
            [],
            $this->apiTokenHeaders($token)
        );

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        Mail::assertSent(LibraryCardPickupReminderMail::class);

        $card->refresh();
        $this->assertSame(LibraryCard::WORKFLOW_ACTIVE, $card->workflow_status);
        $this->assertSame(LibraryCardStatus::ACTIVE, $card->status);
        $this->assertNotNull($card->issue_date);
        $this->assertNotNull($card->expiry_date);
        $this->assertNull(data_get($card->params, 'payment_due_at'));
        $this->assertSame($librarian->id, $card->reviewed_by);
    }

    public function test_approve_fails_when_not_pending_review(): void
    {
        Mail::fake();

        $faculty = Faculty::query()->create(['code' => 'FB', 'name' => 'B', 'is_active' => true]);
        $period = Period::query()->create(['code' => 'PB', 'name' => 'P2', 'is_active' => true]);
        $reader = User::factory()->create([
            'user_type' => RoleType::STUDENT,
            'avatar' => 'avatars/y.jpg',
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'L2',
        ]);

        $card = app(LibraryCardService::class)->createForUserHaveAccount($reader, [
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'L2',
        ]);
        $card->update(['workflow_status' => LibraryCard::WORKFLOW_PENDING_PAYMENT]);

        [, $token] = $this->createLibrarianUserAndToken();

        $this->postJson(
            "/api/v1/library-cards/{$card->id}/approve-review",
            [],
            $this->apiTokenHeaders($token)
        )->assertStatus(422);

        Mail::assertNothingSent();
    }

    public function test_student_token_cannot_approve(): void
    {
        $faculty = Faculty::query()->create(['code' => 'FC', 'name' => 'C', 'is_active' => true]);
        $period = Period::query()->create(['code' => 'PC', 'name' => 'P3', 'is_active' => true]);
        $reader = User::factory()->create([
            'user_type' => RoleType::STUDENT,
            'avatar' => 'avatars/z.jpg',
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'L3',
        ]);
        $card = app(LibraryCardService::class)->createForUserHaveAccount($reader, [
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'L3',
        ]);

        $token = JWTAuth::fromUser($reader);

        $this->postJson(
            "/api/v1/library-cards/{$card->id}/approve-review",
            [],
            $this->apiTokenHeaders($token)
        )->assertStatus(403);
    }

    public function test_librarian_reject_sets_rejected_and_soft_deletes(): void
    {
        $faculty = Faculty::query()->create(['code' => 'FR', 'name' => 'R', 'is_active' => true]);
        $period = Period::query()->create(['code' => 'PR', 'name' => 'P', 'is_active' => true]);
        $reader = User::factory()->create([
            'user_type' => RoleType::STUDENT,
            'avatar' => 'avatars/r.jpg',
            'email' => 'reader-reject@example.com',
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'LR',
        ]);

        $card = app(LibraryCardService::class)->createForUserHaveAccount($reader, [
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'LR',
        ]);

        [$librarian, $token] = $this->createLibrarianUserAndToken();

        $response = $this->postJson(
            "/api/v1/library-cards/{$card->id}/reject-review",
            ['notes' => 'Không đủ điều kiện'],
            $this->apiTokenHeaders($token)
        );

        $response->assertStatus(200)->assertJsonPath('status', 'success');

        $card = LibraryCard::withTrashed()->findOrFail($card->id);
        $this->assertNotNull($card->deleted_at);
        $this->assertSame(LibraryCard::WORKFLOW_REJECTED, $card->workflow_status);
        $this->assertSame('Không đủ điều kiện', $card->notes);
        $this->assertSame($librarian->id, $card->reviewed_by);
    }

    public function test_reject_fails_when_not_pending_review(): void
    {
        $faculty = Faculty::query()->create(['code' => 'FR2', 'name' => 'R2', 'is_active' => true]);
        $period = Period::query()->create(['code' => 'PR2', 'name' => 'P', 'is_active' => true]);
        $reader = User::factory()->create([
            'user_type' => RoleType::STUDENT,
            'avatar' => 'avatars/r2.jpg',
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'LR2',
        ]);

        $card = app(LibraryCardService::class)->createForUserHaveAccount($reader, [
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'LR2',
        ]);
        $card->update(['workflow_status' => LibraryCard::WORKFLOW_PENDING_PAYMENT]);

        [, $token] = $this->createLibrarianUserAndToken();

        $this->postJson(
            "/api/v1/library-cards/{$card->id}/reject-review",
            [],
            $this->apiTokenHeaders($token)
        )->assertStatus(422);
    }
}
