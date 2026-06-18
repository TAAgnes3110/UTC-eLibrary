<?php

namespace Tests\Feature\Backend;

use App\Enums\LibraryCardStatus;
use App\Enums\RoleType;
use App\Models\Faculty;
use App\Models\LibraryCard;
use App\Models\Period;
use App\Models\User;
use App\Services\LibraryCard\LibraryCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryCardStaffDirectUpdateTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    public function test_librarian_can_set_workflow_active_via_update(): void
    {
        $faculty = Faculty::query()->create(['code' => 'F1', 'name' => 'CNTT', 'is_active' => true]);
        $period = Period::query()->create(['code' => 'P1', 'name' => 'K63', 'is_active' => true]);
        $reader = User::factory()->create([
            'user_type' => RoleType::STUDENT,
            'avatar' => 'avatars/test.jpg',
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'DH12',
        ]);

        $card = app(LibraryCardService::class)->createForUserHaveAccount($reader, [
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'DH12',
        ]);
        $card->update([
            'workflow_status' => LibraryCard::WORKFLOW_PENDING_PAYMENT,
            'status' => LibraryCardStatus::PENDING,
        ]);

        [, $token] = $this->createLibrarianUserAndToken();

        $response = $this->putJson(
            "/api/v1/library-cards/{$card->id}",
            [
                'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
                'status' => LibraryCardStatus::ACTIVE->value,
            ],
            $this->apiTokenHeaders($token)
        );

        $response->assertOk()->assertJsonPath('status', 'success');

        $card->refresh();
        $this->assertSame(LibraryCard::WORKFLOW_ACTIVE, $card->workflow_status);
        $this->assertSame(LibraryCardStatus::ACTIVE, $card->status);
        $this->assertNotNull($card->issue_date);
        $this->assertNotNull($card->expiry_date);
    }

    public function test_update_accepts_legacy_draft_workflow_status(): void
    {
        $faculty = Faculty::query()->create(['code' => 'F2', 'name' => 'KT', 'is_active' => true]);
        $period = Period::query()->create(['code' => 'P2', 'name' => 'K64', 'is_active' => true]);
        $reader = User::factory()->create([
            'user_type' => RoleType::STUDENT,
            'avatar' => 'avatars/test.jpg',
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'DH13',
        ]);

        $card = app(LibraryCardService::class)->createForUserHaveAccount($reader, [
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'DH13',
        ]);
        $card->update(['workflow_status' => LibraryCard::WORKFLOW_DRAFT]);

        [, $token] = $this->createLibrarianUserAndToken();

        $response = $this->putJson(
            "/api/v1/library-cards/{$card->id}",
            [
                'workflow_status' => LibraryCard::WORKFLOW_DRAFT,
                'status' => LibraryCardStatus::ACTIVE->value,
                'faculty_id' => $faculty->id,
                'period_id' => $period->id,
                'class_code' => 'DH13',
            ],
            $this->apiTokenHeaders($token)
        );

        $response->assertOk()->assertJsonPath('status', 'success');
        $card->refresh();
        $this->assertSame(LibraryCard::WORKFLOW_PENDING_REVIEW, $card->workflow_status);
    }
}
