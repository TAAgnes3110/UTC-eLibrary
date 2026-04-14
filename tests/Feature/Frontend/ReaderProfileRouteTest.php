<?php

namespace Tests\Feature\Frontend;

use App\Enums\RoleType;
use App\Models\User;
use Tests\TestCase;

class ReaderProfileRouteTest extends TestCase
{
    public function test_reader_profile_requires_authentication(): void
    {
        $this->get(route('reader.profile'))->assertRedirect();
    }

    public function test_reader_profile_renders_for_student(): void
    {
        $user = User::factory()->create(['user_type' => RoleType::STUDENT]);

        $this->actingAs($user)->get(route('reader.profile'))->assertOk();
    }
}
