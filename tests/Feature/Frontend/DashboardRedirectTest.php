<?php

namespace Tests\Feature\Frontend;

use App\Enums\RoleType;
use App\Models\User;
use Tests\TestCase;

class DashboardRedirectTest extends TestCase
{
    public function test_dashboard_redirects_student_to_reader_home(): void
    {
        $user = User::factory()->create(['user_type' => RoleType::STUDENT]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('reader.home'));
    }

    public function test_dashboard_redirects_librarian_to_admin_dashboard(): void
    {
        $user = User::factory()->create(['user_type' => RoleType::LIBRARIAN]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('admin.dashboard'));
    }
}
