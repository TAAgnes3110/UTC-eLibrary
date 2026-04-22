<?php

namespace Tests\Feature\Frontend;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWarehousePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_warehouse_pages_require_authentication(): void
    {
        $this->get(route('admin.warehouses.index'))->assertRedirect(route('login'));
        $this->get(route('admin.warehouses.storage-cabinets'))->assertRedirect(route('login'));
        $this->get(route('admin.warehouses.storage-slots'))->assertRedirect(route('login'));
    }

    public function test_librarian_can_open_admin_warehouse_pages(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['user_type' => RoleType::LIBRARIAN]);

        $this->actingAs($user)->get(route('admin.warehouses.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.warehouses.storage'))->assertOk();
        $this->actingAs($user)->get(route('admin.warehouses.storage-cabinets'))->assertOk();
        $this->actingAs($user)->get(route('admin.warehouses.storage-slots'))->assertOk();
    }
}

