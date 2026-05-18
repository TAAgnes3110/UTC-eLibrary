<?php

namespace Tests\Feature\Modules;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Module: Truy cập FE Inertia (10 case).
 */
class FrontendAccessModuleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function case01_home_public_ok(): void
    {
        $this->get('/')->assertOk();
    }

    #[Test]
    public function case02_catalog_public_ok(): void
    {
        $this->get('/tra-cuu-sach')->assertOk();
    }

    #[Test]
    public function case03_login_page_guest_ok(): void
    {
        $this->get('/login')->assertOk();
    }

    #[Test]
    public function case04_admin_users_redirects_guest_to_login(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function case05_student_forbidden_admin_users(): void
    {
        $this->actingAs(User::factory()->create(['user_type' => RoleType::STUDENT]))
            ->get(route('admin.users.index'))->assertStatus(403);
    }

    #[Test]
    public function case06_student_forbidden_admin_books(): void
    {
        $this->actingAs(User::factory()->create(['user_type' => RoleType::STUDENT]))
            ->get(route('admin.books.index'))->assertStatus(403);
    }

    #[Test]
    public function case07_librarian_can_open_admin_loans(): void
    {
        $this->actingAs(User::factory()->create(['user_type' => RoleType::LIBRARIAN]))
            ->get(route('admin.loans.index'))->assertOk();
    }

    #[Test]
    public function case08_student_dashboard_redirects_reader_home(): void
    {
        $this->actingAs(User::factory()->create(['user_type' => RoleType::STUDENT]))
            ->get('/dashboard')->assertRedirect(route('reader.home'));
    }

    #[Test]
    public function case09_librarian_dashboard_redirects_admin(): void
    {
        $this->actingAs(User::factory()->create(['user_type' => RoleType::LIBRARIAN]))
            ->get('/dashboard')->assertRedirect(route('admin.dashboard'));
    }

    #[Test]
    public function case10_digital_payment_requires_auth(): void
    {
        $this->get('/dich-vu/thanh-toan')->assertRedirect();
    }
}
