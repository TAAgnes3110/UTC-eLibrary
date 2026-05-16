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

class MeLibraryCardReplaceTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    public function test_reader_can_replace_pending_review_application(): void
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

        $old = app(LibraryCardService::class)->createForUserHaveAccount($reader, [
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'CNTT1',
            'full_name' => 'Tên cũ',
        ]);
        $this->assertSame(LibraryCard::WORKFLOW_PENDING_REVIEW, $old->workflow_status);

        $token = JWTAuth::fromUser($reader);

        $response = $this->postJson(
            '/api/v1/me/library-card/replace',
            [
                'faculty_id' => $faculty->id,
                'period_id' => $period->id,
                'class_code' => 'CNTT1',
                'full_name' => 'Tên mới',
                'photo_path' => 'avatars/c.jpg',
            ],
            $this->apiTokenHeaders($token),
        );

        $response->assertStatus(201)->assertJsonPath('status', 'success');

        $old->refresh();
        $this->assertSame(LibraryCard::WORKFLOW_CANCELLED, $old->workflow_status);
        $this->assertSoftDeleted('library_cards', ['id' => $old->id]);

        $new = LibraryCard::query()
            ->where('user_id', $reader->id)
            ->whereNull('deleted_at')
            ->latest('id')
            ->first();
        $this->assertNotNull($new);
        $this->assertNotSame($old->id, $new->id);
        $this->assertSame(LibraryCard::WORKFLOW_PENDING_REVIEW, $new->workflow_status);
        $this->assertSame('Tên mới', $new->full_name);
    }

    public function test_replace_fails_when_pending_payment(): void
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
        $card->update(['workflow_status' => LibraryCard::WORKFLOW_PENDING_PAYMENT]);

        $token = JWTAuth::fromUser($reader);

        $response = $this->postJson(
            '/api/v1/me/library-card/replace',
            [
                'faculty_id' => $faculty->id,
                'period_id' => $period->id,
                'class_code' => 'CNTT1',
                'full_name' => 'Tên mới',
                'photo_path' => 'avatars/c.jpg',
            ],
            $this->apiTokenHeaders($token),
        );

        $response->assertStatus(422);
    }
}
