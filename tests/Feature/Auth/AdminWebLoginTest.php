<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminWebLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_login_via_web_login_route(): void
    {
        [$user] = $this->createStaffUser(RoleType::SUPER_ADMIN, 'admin@utc.test');

        $response = $this->postJson('/login', [
            'login' => $user->email,
            'password' => 'password',
            'remember' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['token', 'user']);
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_login_survives_broken_spatie_role_pivot(): void
    {
        [$user, $role] = $this->createStaffUser(RoleType::ADMIN, 'broken-admin@utc.test');
        $role->delete();

        $response = $this->postJson('/login', [
            'login' => $user->email,
            'password' => 'password',
            'remember' => false,
        ]);

        $response->assertOk()->assertJsonPath('status', 'success');
    }

    /**
     * @return array{0: User, 1: Role}
     */
    private function createStaffUser(RoleType $type, string $email): array
    {
        $guard = 'api';
        $role = Role::firstOrCreate(
            ['name' => $type->value, 'guard_name' => $guard],
            ['name' => $type->value, 'guard_name' => $guard]
        );
        $user = User::factory()->create([
            'user_type' => $type,
            'email' => $email,
            'password' => 'password',
        ]);
        $user->assignRole($role);

        return [$user, $role];
    }
}
