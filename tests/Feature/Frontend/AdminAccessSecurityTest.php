<?php

namespace Tests\Feature\Frontend;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Trang admin Inertia — độc giả không được truy cập dù đã đăng nhập.
 */
class AdminAccessSecurityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function student_is_forbidden_from_admin_users_page(): void
    {
        $student = User::factory()->create(['user_type' => RoleType::STUDENT]);

        $this->actingAs($student)
            ->get(route('admin.users.index'))
            ->assertStatus(403);
    }

    #[Test]
    public function student_is_forbidden_from_admin_books_page(): void
    {
        $student = User::factory()->create(['user_type' => RoleType::STUDENT]);

        $this->actingAs($student)
            ->get(route('admin.books.index'))
            ->assertStatus(403);
    }

    #[Test]
    public function student_is_forbidden_from_admin_loans_page(): void
    {
        $student = User::factory()->create(['user_type' => RoleType::STUDENT]);

        $this->actingAs($student)
            ->get(route('admin.loans.index'))
            ->assertStatus(403);
    }

    #[Test]
    public function guest_is_redirected_to_login_from_admin_dashboard(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }
}
