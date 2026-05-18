<?php

namespace Tests\Feature\Modules;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ModuleTestHelpers;
use Tests\TestCase;

/**
 * Module: Thẻ thư viện (10 case).
 */
class LibraryCardModuleTest extends TestCase
{
    use ModuleTestHelpers;
    use RefreshDatabase;

    #[Test]
    public function case01_guest_cannot_list_admin_cards(): void
    {
        $this->getJson('/api/v1/library-cards')->assertStatus(401);
    }

    #[Test]
    public function case02_student_cannot_list_admin_cards(): void
    {
        [, $h] = $this->studentContext();
        $this->getJson('/api/v1/library-cards', $h)->assertStatus(403);
    }

    #[Test]
    public function case03_admin_can_list_cards(): void
    {
        [, $h] = $this->adminContext();
        $this->getJson('/api/v1/library-cards', $h)->assertStatus(200);
    }

    #[Test]
    public function case04_me_request_card_without_auth_returns_401(): void
    {
        $this->postJson('/api/v1/me/library-card', [])->assertStatus(401);
    }

    #[Test]
    public function case05_me_request_card_missing_fields_returns_422(): void
    {
        [, $h] = $this->studentContext();
        $this->postJson('/api/v1/me/library-card', [], $h)->assertStatus(422);
    }

    #[Test]
    public function case06_guest_register_without_body_returns_422(): void
    {
        $this->postJson('/api/v1/library-cards/guest-register', [])->assertStatus(422);
    }

    #[Test]
    public function case07_lookup_for_loan_requires_auth(): void
    {
        $this->getJson('/api/v1/library-cards/lookup-for-loan')->assertStatus(401);
    }

    #[Test]
    public function case08_show_nonexistent_card_returns_404(): void
    {
        [, $h] = $this->adminContext();
        $this->getJson('/api/v1/library-cards/9999999', $h)->assertStatus(404);
    }

    #[Test]
    public function case09_trash_list_returns_200(): void
    {
        [, $h] = $this->adminContext();
        $this->getJson('/api/v1/library-cards/trash', $h)->assertStatus(200);
    }

    #[Test]
    public function case10_export_returns_downloadable_response(): void
    {
        [, $h] = $this->adminContext();
        $this->getJson('/api/v1/library-cards/export', $h)->assertSuccessful();
    }
}
