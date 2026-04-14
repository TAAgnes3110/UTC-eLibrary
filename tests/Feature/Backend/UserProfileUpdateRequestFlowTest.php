<?php

namespace Tests\Feature\Backend;

use App\Models\Faculty;
use App\Models\User;
use App\Models\UserProfileUpdateRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserProfileUpdateRequestFlowTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    public function test_staff_cannot_submit_profile_update_request(): void
    {
        Storage::fake('public');
        [, $adminToken] = $this->createAdminUserAndToken();
        $faculty = Faculty::query()->create([
            'code' => 'CNTT',
            'name' => 'Cong nghe thong tin',
            'is_active' => true,
        ]);
        $proof = UploadedFile::fake()->image('proof.jpg');

        $response = $this->post(
            '/api/v1/me/profile-update-requests',
            [
                'requested_faculty_id' => $faculty->id,
                'proof_image' => $proof,
            ],
            $this->apiTokenHeaders($adminToken)
        );

        $response->assertStatus(403);
        $this->assertDatabaseCount('user_profile_update_requests', 0);
    }

    public function test_user_submit_update_request_requires_proof_image(): void
    {
        [$user, $token] = $this->createUserAndToken([
            'code' => '012345678901',
            'class_code' => 'CNTT1',
        ]);
        $faculty = Faculty::query()->create([
            'code' => 'CNTT',
            'name' => 'Cong nghe thong tin',
            'is_active' => true,
        ]);

        $response = $this->postJson(
            '/api/v1/me/profile-update-requests',
            [
                'requested_code' => '123456789123',
                'requested_faculty_id' => $faculty->id,
            ],
            $this->apiTokenHeaders($token)
        );

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');

        $this->assertDatabaseCount('user_profile_update_requests', 0);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'code' => '012345678901',
        ]);
    }

    public function test_admin_can_approve_and_apply_profile_update_request(): void
    {
        [$admin, $adminToken] = $this->createAdminUserAndToken();
        [$user, $token] = $this->createUserAndToken([
            'code' => '012345678901',
            'class_code' => 'K61-CNTT',
        ]);

        $oldFaculty = Faculty::query()->create([
            'code' => 'OLD',
            'name' => 'Old Faculty',
            'is_active' => true,
        ]);
        $newFaculty = Faculty::query()->create([
            'code' => 'NEW',
            'name' => 'New Faculty',
            'is_active' => true,
        ]);
        $user->update(['faculty_id' => $oldFaculty->id]);

        $proof = UploadedFile::fake()->image('proof.jpg');
        $submitResponse = $this->post(
            '/api/v1/me/profile-update-requests',
            [
                'requested_code' => '111222333444',
                'requested_faculty_id' => $newFaculty->id,
                'requested_class_code' => 'K62-CNTT',
                'proof_image' => $proof,
            ],
            $this->apiTokenHeaders($token)
        );
        $submitResponse->assertStatus(201);

        /** @var UserProfileUpdateRequest $requestRecord */
        $requestRecord = UserProfileUpdateRequest::query()->firstOrFail();

        $approveResponse = $this->postJson(
            '/api/v1/users/profile-update-requests/'.$requestRecord->id.'/approve',
            ['review_note' => 'Ho so hop le'],
            $this->apiTokenHeaders($adminToken)
        );

        $approveResponse->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', UserProfileUpdateRequest::STATUS_APPROVED);

        $user->refresh();
        $requestRecord->refresh();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'code' => '111222333444',
            'faculty_id' => $newFaculty->id,
            'class_code' => 'K62-CNTT',
        ]);
        $this->assertDatabaseHas('user_profile_update_requests', [
            'id' => $requestRecord->id,
            'status' => UserProfileUpdateRequest::STATUS_APPROVED,
            'reviewed_by' => $admin->id,
        ]);
    }
}

