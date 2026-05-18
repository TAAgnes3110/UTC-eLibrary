<?php

namespace Tests\Feature\Modules;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ModuleTestHelpers;
use Tests\TestCase;

/**
 * Module: Sách admin (10 case).
 */
class BookModuleTest extends TestCase
{
    use ModuleTestHelpers;
    use RefreshDatabase;

    private function seedIds(): array
    {
        $now = now();
        $cid = DB::table('classifications')->insertGetId([
            'code' => 'C-'.uniqid(), 'name' => 'T', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $wid = DB::table('warehouses')->insertGetId([
            'code' => 'W-'.uniqid(), 'name' => 'T', 'is_active' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        return [$cid, $wid];
    }

    #[Test]
    public function case01_unauthenticated_books_returns_401(): void
    {
        $this->getJson('/api/v1/books')->assertStatus(401);
    }

    #[Test]
    public function case02_student_forbidden_books_list(): void
    {
        [, $h] = $this->studentContext();
        $this->getJson('/api/v1/books', $h)->assertStatus(403);
    }

    #[Test]
    public function case03_admin_lists_books(): void
    {
        [, $h] = $this->adminContext();
        $this->getJson('/api/v1/books', $h)->assertStatus(200);
    }

    #[Test]
    public function case04_create_book_without_title_returns_422(): void
    {
        [$cid, $wid] = $this->seedIds();
        [, $h] = $this->adminContext();
        $this->postJson('/api/v1/books', [
            'classification_id' => $cid, 'warehouse_id' => $wid,
            'quantity' => 1, 'resource_type' => 'textbook', 'access_mode' => 'circulation_only',
        ], $h)->assertStatus(422);
    }

    #[Test]
    public function case05_create_book_valid_returns_201(): void
    {
        [$cid, $wid] = $this->seedIds();
        [, $h] = $this->adminContext();
        $this->postJson('/api/v1/books', [
            'title' => 'Sách test', 'classification_id' => $cid, 'warehouse_id' => $wid,
            'quantity' => 1, 'resource_type' => 'textbook', 'access_mode' => 'circulation_only',
        ], $h)->assertStatus(201);
    }

    #[Test]
    public function case06_show_nonexistent_book_returns_404(): void
    {
        [, $h] = $this->adminContext();
        $this->getJson('/api/v1/books/9999999', $h)->assertStatus(404);
    }

    #[Test]
    public function case07_trash_list_returns_200(): void
    {
        [, $h] = $this->adminContext();
        $this->getJson('/api/v1/books/trash', $h)->assertStatus(200);
    }

    #[Test]
    public function case08_import_template_returns_200(): void
    {
        [, $h] = $this->adminContext();
        $this->getJson('/api/v1/books/import-template', $h)->assertSuccessful();
    }

    #[Test]
    public function case09_invalid_classification_on_create_returns_422(): void
    {
        [, $wid] = $this->seedIds();
        [, $h] = $this->adminContext();
        $this->postJson('/api/v1/books', [
            'title' => 'X', 'classification_id' => 999999, 'warehouse_id' => $wid,
            'quantity' => 1, 'resource_type' => 'textbook', 'access_mode' => 'circulation_only',
        ], $h)->assertStatus(422);
    }

    #[Test]
    public function case10_librarian_can_list_books(): void
    {
        [, $h] = $this->librarianContext();
        $this->getJson('/api/v1/books', $h)->assertStatus(200);
    }
}
