<?php

namespace Tests\Feature\Api;

use App\Enums\RoleType;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();

        $this->admin = User::factory()->create([
            'user_type' => RoleType::ADMIN,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'code' => '001122334455',
            'email' => 'user1@utc.edu.vn',
            'phone' => '0900000001',
            'user_type' => RoleType::STUDENT,
            'is_active' => true,
        ]);
    }

    protected function putAsAdmin(array $payload)
    {
        return $this->actingAs($this->admin)
            ->json('PUT', "/api/v1/users/{$this->user->id}", $payload);
    }

    /** @test */
    public function can_update_only_name()
    {
        $response = $this->putAsAdmin(['name' => 'Tên mới']);

        $response->assertOk();
        $this->assertEquals('Tên mới', $this->user->fresh()->name);
        $this->assertEquals('001122334455', $this->user->fresh()->code);
        $this->assertEquals('user1@utc.edu.vn', $this->user->fresh()->email);
    }

    /** @test */
    public function can_update_email_only_and_must_be_unique()
    {
        User::factory()->create(['email' => 'existing@utc.edu.vn']);

        $duplicate = $this->putAsAdmin(['email' => 'existing@utc.edu.vn']);
        $duplicate->assertStatus(422)->assertJsonValidationErrors(['email']);

        $ok = $this->putAsAdmin(['email' => 'new@utc.edu.vn']);
        $ok->assertOk();
        $this->assertEquals('new@utc.edu.vn', $this->user->fresh()->email);
    }

    /** @test */
    public function can_update_multiple_fields_without_sending_all_required_fields()
    {
        $response = $this->putAsAdmin([
            'name' => 'Tên mới',
            'phone' => '0911111111',
            'cohort' => 'K66',
        ]);

        $response->assertOk();
        $user = $this->user->fresh();
        $this->assertEquals('Tên mới', $user->name);
        $this->assertEquals('0911111111', $user->phone);
        $this->assertEquals('K66', $user->cohort);
    }

    /** @test */
    public function empty_password_is_ignored_on_update()
    {
        $oldPasswordHash = $this->user->password;

        $response = $this->putAsAdmin(['password' => '']);

        $response->assertOk();
        $this->assertEquals($oldPasswordHash, $this->user->fresh()->password);
    }

    /** @test */
    public function cannot_change_code_via_update_endpoint()
    {
        $response = $this->putAsAdmin(['code' => '009998887776']);

        $response->assertOk();
        $this->assertEquals('001122334455', $this->user->fresh()->code);
    }

    /** @test */
    public function empty_body_does_not_fail_and_changes_nothing()
    {
        $original = $this->user->toArray();

        $response = $this->putAsAdmin([]);

        $response->assertOk();
        $fresh = $this->user->fresh();
        $this->assertEquals($original['name'], $fresh->name);
        $this->assertEquals($original['email'], $fresh->email);
        $this->assertEquals($original['code'], $fresh->code);
    }

    /** @test */
    public function invalid_faculty_id_is_rejected()
    {
        $response = $this->putAsAdmin(['faculty_id' => -1]);

        $response->assertStatus(422)->assertJsonValidationErrors(['faculty_id']);
    }

    /** @test */
    public function can_update_faculty_department_and_status()
    {
        $faculty = Faculty::create([
            'code' => 'F01',
            'name' => 'Khoa thử nghiệm',
            'is_active' => true,
        ]);

        $department = Department::create([
            'faculty_id' => $faculty->id,
            'code' => 'D01',
            'name' => 'Bộ môn thử nghiệm',
            'is_active' => true,
        ]);

        $response = $this->putAsAdmin([
            'faculty_id' => $faculty->id,
            'department_id' => $department->id,
            'is_active' => false,
        ]);

        $response->assertOk();
        $user = $this->user->fresh();
        $this->assertEquals($faculty->id, $user->faculty_id);
        $this->assertEquals($department->id, $user->department_id);
        $this->assertFalse((bool) $user->is_active);
    }

    /** @test */
    public function can_update_user_type_when_allowed()
    {
        $response = $this->putAsAdmin([
            'user_type' => RoleType::LIBRARIAN->value,
        ]);

        $response->assertOk();
        $this->assertEquals(RoleType::LIBRARIAN, $this->user->fresh()->user_type);
    }
}
