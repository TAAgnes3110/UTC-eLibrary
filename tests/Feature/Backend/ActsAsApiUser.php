<?php

namespace Tests\Feature\Backend;

use App\Enums\RoleType;
use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Spatie\Permission\Models\Role;

trait ActsAsApiUser
{
    /**
     * Tạo user (MEMBER) + JWT token để gọi API /me/*, master-data.
     *
     * @return array{0: User, 1: string}
     */
    protected function createUserAndToken(array $overrides = []): array
    {
        $user = User::factory()->create(array_merge([
            'user_type' => RoleType::MEMBER,
            'email' => 'reader@test.com',
            'password' => 'password',
        ], $overrides));
        $token = JWTAuth::fromUser($user);
        return [$user, $token];
    }

    /**
     * Tạo user có role SUPER_ADMIN + JWT token để gọi API admin.
     *
     * @param array<string, mixed> $overrides
     * @return array{0: User, 1: string}
     */
    protected function createAdminUserAndToken(array $overrides = []): array
    {
        $guard = 'api';
        $role = Role::firstOrCreate(
            ['name' => RoleType::SUPER_ADMIN->value, 'guard_name' => $guard],
            ['name' => RoleType::SUPER_ADMIN->value, 'guard_name' => $guard]
        );
        $user = User::factory()->create(array_merge([
            'user_type' => RoleType::SUPER_ADMIN,
            'password' => 'password',
        ], $overrides));
        $user->assignRole($role);
        $token = JWTAuth::fromUser($user);
        return [$user, $token];
    }

    protected function apiTokenHeaders(string $token): array
    {
        return ['Authorization' => 'Bearer ' . $token];
    }
}
