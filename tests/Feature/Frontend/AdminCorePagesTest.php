<?php

namespace Tests\Feature\Frontend;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCorePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_core_pages_require_authentication(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
        $this->get(route('admin.books.index'))->assertRedirect(route('login'));
        $this->get(route('admin.library-cards.index'))->assertRedirect(route('login'));
        $this->get(route('admin.loans.index'))->assertRedirect(route('login'));
        $this->get(route('admin.library-settings.index'))->assertRedirect(route('login'));
        $this->get(route('admin.library-settings.pricing'))->assertRedirect(route('login'));
    }

    public function test_librarian_can_open_admin_core_pages(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['user_type' => RoleType::LIBRARIAN]);

        $this->actingAs($user)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.books.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.library-cards.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.loans.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.library-settings.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.library-settings.pricing'))->assertOk();
    }
}
