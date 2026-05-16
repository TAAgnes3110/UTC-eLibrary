<?php

namespace Tests\Feature\Backend;

use App\Enums\RoleType;
use App\Models\Faculty;
use App\Models\LibraryCard;
use App\Models\Period;
use App\Models\User;
use App\Services\LibraryCard\LibraryCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class MeLibraryCardCancelTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    public function test_reader_can_cancel_pending_review_application(): void
    {
        $faculty = Faculty::query()->create(['code' => 'FC', 'name' => 'Khoa C', 'is_active' => true]);
        $period = Period::query()->create(['code' => 'PC', 'name' => 'NK C', 'is_active' => true]);
        $reader = User::factory()->create([
            'user_type' => RoleType::STUDENT,
            'avatar' => 'avatars/c.jpg',
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'CNTT1',
        ]);

        $card = app(LibraryCardService::class)->createForUserHaveAccount($reader, [
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'CNTT1',
        ]);
        $this->assertSame(LibraryCard::WORKFLOW_PENDING_REVIEW, $card->workflow_status);

        $token = JWTAuth::fromUser($reader);

        $response = $this->deleteJson('/api/v1/me/library-card', [], $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonPath('status', 'success');

        $card->refresh();
        $this->assertSame(LibraryCard::WORKFLOW_CANCELLED, $card->workflow_status);
        $this->assertSoftDeleted('library_cards', ['id' => $card->id]);
    }

    public function test_cancel_fails_when_no_card(): void
    {
        [$reader, $token] = $this->createUserAndToken([
            'avatar' => 'avatars/n.jpg',
        ]);

        $response = $this->deleteJson('/api/v1/me/library-card', [], $this->apiTokenHeaders($token));

        $response->assertStatus(422);
    }
}
