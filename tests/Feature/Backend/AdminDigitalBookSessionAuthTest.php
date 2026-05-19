<?php

namespace Tests\Feature\Backend;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDigitalBookSessionAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_digital_book_with_web_session_without_bearer(): void
    {
        Storage::fake('local');

        $guard = 'api';
        $role = Role::firstOrCreate(
            ['name' => RoleType::SUPER_ADMIN->value, 'guard_name' => $guard],
            ['name' => RoleType::SUPER_ADMIN->value, 'guard_name' => $guard]
        );
        $admin = User::factory()->create([
            'user_type' => RoleType::SUPER_ADMIN,
            'password' => 'password',
        ]);
        $admin->assignRole($role);

        $pdf = UploadedFile::fake()->create('luận-văn.pdf', 200, 'application/pdf');

        $response = $this->actingAs($admin, 'web')
            ->withHeader('domain', 'http://localhost')
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withoutMiddleware(ValidateCsrfToken::class)
            ->post('/api/v1/books/digital', [
                'title' => 'Đồ án session test',
                'file' => $pdf,
                'is_primary' => true,
                'visibility' => 'public',
            ]);

        $response->assertCreated()->assertJsonPath('status', 'success');
    }

    public function test_admin_can_update_digital_book_with_web_session_post_multipart(): void
    {
        Storage::fake('local');

        $guard = 'api';
        $role = Role::firstOrCreate(
            ['name' => RoleType::SUPER_ADMIN->value, 'guard_name' => $guard],
            ['name' => RoleType::SUPER_ADMIN->value, 'guard_name' => $guard]
        );
        $admin = User::factory()->create([
            'user_type' => RoleType::SUPER_ADMIN,
            'password' => 'password',
        ]);
        $admin->assignRole($role);

        $pdf = UploadedFile::fake()->create('luận-văn.pdf', 200, 'application/pdf');

        $create = $this->actingAs($admin, 'web')
            ->withHeader('domain', 'http://localhost')
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withoutMiddleware(ValidateCsrfToken::class)
            ->post('/api/v1/books/digital', [
                'title' => 'Đồ án session update',
                'file' => $pdf,
            ]);

        $create->assertCreated();
        $bookId = (int) $create->json('data.id');

        $newPdf = UploadedFile::fake()->create('cap-nhat.pdf', 150, 'application/pdf');

        $update = $this->actingAs($admin, 'web')
            ->withHeader('domain', 'http://localhost')
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withoutMiddleware(ValidateCsrfToken::class)
            ->post("/api/v1/books/{$bookId}/digital", [
                'title' => 'Đồ án session đã sửa',
                'file' => $newPdf,
            ]);

        $update->assertOk()->assertJsonPath('data.title', 'Đồ án session đã sửa');
    }
}
