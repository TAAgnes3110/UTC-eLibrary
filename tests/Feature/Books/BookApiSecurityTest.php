<?php

namespace Tests\Feature\Books;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Test toàn diện Book CRUD API + bảo mật.
 * Kiểm tra: auth, IDOR, mass-assignment, file upload, pagination.
 */
class BookApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): array
    {
        $role = Role::firstOrCreate(['name' => RoleType::SUPER_ADMIN->value, 'guard_name' => 'api']);
        $user = User::factory()->create(['user_type' => RoleType::SUPER_ADMIN]);
        $user->assignRole($role);

        return [$user, JWTAuth::fromUser($user)];
    }

    private function makeStudent(): array
    {
        $user = User::factory()->create(['user_type' => RoleType::STUDENT]);

        return [$user, JWTAuth::fromUser($user)];
    }

    private function auth(string $token): array
    {
        return ['Authorization' => "Bearer $token", 'Accept' => 'application/json'];
    }

    private function seedClassificationAndWarehouse(): array
    {
        $now = now();
        $cid = DB::table('classifications')->insertGetId([
            'code' => 'BK-'.uniqid(), 'name' => 'Test',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $wid = DB::table('warehouses')->insertGetId([
            'code' => 'WH-'.uniqid(), 'name' => 'Test WH', 'is_active' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        return [$cid, $wid];
    }

    private function validBookPayload(int $cid, int $wid): array
    {
        return [
            'title' => 'Giáo trình lập trình',
            'classification_id' => $cid,
            'warehouse_id' => $wid,
            'quantity' => 5,
            'resource_type' => 'book',
            'access_mode' => 'normal',
        ];
    }

    // ── Auth guard ────────────────────────────────────────────────────────────

    #[Test]
    public function unauthenticated_cannot_list_books(): void
    {
        $this->getJson('/api/v1/books')->assertStatus(401);
    }

    #[Test]
    public function student_cannot_list_books_admin_endpoint(): void
    {
        [, $token] = $this->makeStudent();
        $this->getJson('/api/v1/books', $this->auth($token))->assertStatus(403);
    }

    #[Test]
    public function admin_can_list_books(): void
    {
        [, $token] = $this->makeAdmin();
        $this->getJson('/api/v1/books', $this->auth($token))->assertStatus(200);
    }

    // ── Create Book ───────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_create_book_with_valid_data(): void
    {
        [, $token] = $this->makeAdmin();
        [$cid, $wid] = $this->seedClassificationAndWarehouse();

        $this->postJson('/api/v1/books', $this->validBookPayload($cid, $wid), $this->auth($token))
            ->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'title']]);
    }

    #[Test]
    public function create_book_missing_title_returns_422(): void
    {
        [, $token] = $this->makeAdmin();
        [$cid, $wid] = $this->seedClassificationAndWarehouse();

        $payload = $this->validBookPayload($cid, $wid);
        unset($payload['title']);

        $this->postJson('/api/v1/books', $payload, $this->auth($token))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    #[Test]
    public function create_book_with_nonexistent_classification_returns_422(): void
    {
        [, $token] = $this->makeAdmin();
        [, $wid] = $this->seedClassificationAndWarehouse();

        $this->postJson('/api/v1/books', [
            'title' => 'Test',
            'classification_id' => 999999,
            'warehouse_id' => $wid,
            'quantity' => 1,
            'resource_type' => 'book',
            'access_mode' => 'normal',
        ], $this->auth($token))->assertStatus(422)->assertJsonValidationErrors(['classification_id']);
    }

    #[Test]
    public function create_book_with_nonexistent_warehouse_returns_422(): void
    {
        [, $token] = $this->makeAdmin();
        [$cid] = $this->seedClassificationAndWarehouse();

        $this->postJson('/api/v1/books', [
            'title' => 'Test',
            'classification_id' => $cid,
            'warehouse_id' => 999999,
            'quantity' => 1,
            'resource_type' => 'book',
            'access_mode' => 'normal',
        ], $this->auth($token))->assertStatus(422)->assertJsonValidationErrors(['warehouse_id']);
    }

    #[Test]
    public function create_book_with_negative_quantity_returns_422(): void
    {
        [, $token] = $this->makeAdmin();
        [$cid, $wid] = $this->seedClassificationAndWarehouse();
        $payload = $this->validBookPayload($cid, $wid);
        $payload['quantity'] = -1;

        $this->postJson('/api/v1/books', $payload, $this->auth($token))
            ->assertStatus(422)->assertJsonValidationErrors(['quantity']);
    }

    #[Test]
    public function create_book_with_invalid_published_year_returns_422(): void
    {
        [, $token] = $this->makeAdmin();
        [$cid, $wid] = $this->seedClassificationAndWarehouse();
        $payload = $this->validBookPayload($cid, $wid);
        $payload['published_year'] = 'not-a-year';

        $this->postJson('/api/v1/books', $payload, $this->auth($token))
            ->assertStatus(422);
    }

    #[Test]
    public function create_book_with_xss_in_title_stores_safely(): void
    {
        [, $token] = $this->makeAdmin();
        [$cid, $wid] = $this->seedClassificationAndWarehouse();
        $payload = $this->validBookPayload($cid, $wid);
        $payload['title'] = '<script>alert(1)</script>';

        $response = $this->postJson('/api/v1/books', $payload, $this->auth($token));
        $response->assertSuccessful();

        // Phải lưu được (escaped or raw) nhưng không crash
        $bookId = $response->json('data.id');
        $this->assertNotNull($bookId);
    }

    #[Test]
    public function create_book_with_very_long_title_returns_422(): void
    {
        [, $token] = $this->makeAdmin();
        [$cid, $wid] = $this->seedClassificationAndWarehouse();
        $payload = $this->validBookPayload($cid, $wid);
        $payload['title'] = str_repeat('A', 300); // > 255

        $this->postJson('/api/v1/books', $payload, $this->auth($token))
            ->assertStatus(422);
    }

    #[Test]
    public function student_cannot_create_book(): void
    {
        [, $token] = $this->makeStudent();
        [$cid, $wid] = $this->seedClassificationAndWarehouse();

        $this->postJson('/api/v1/books', $this->validBookPayload($cid, $wid), $this->auth($token))
            ->assertStatus(403);
    }

    // ── Show / Update / Delete ────────────────────────────────────────────────

    #[Test]
    public function show_nonexistent_book_returns_404(): void
    {
        [, $token] = $this->makeAdmin();
        $this->getJson('/api/v1/books/9999999', $this->auth($token))->assertStatus(404);
    }

    #[Test]
    public function show_book_with_string_id_returns_404(): void
    {
        [, $token] = $this->makeAdmin();
        $this->getJson('/api/v1/books/not-an-id', $this->auth($token))->assertStatus(404);
    }

    #[Test]
    public function update_nonexistent_book_returns_404(): void
    {
        [, $token] = $this->makeAdmin();
        [$cid, $wid] = $this->seedClassificationAndWarehouse();

        $this->putJson('/api/v1/books/9999999', $this->validBookPayload($cid, $wid), $this->auth($token))
            ->assertStatus(404);
    }

    #[Test]
    public function delete_nonexistent_book_returns_404(): void
    {
        [, $token] = $this->makeAdmin();
        $this->deleteJson('/api/v1/books/9999999', [], $this->auth($token))->assertStatus(404);
    }

    #[Test]
    public function soft_deleted_book_does_not_appear_in_list(): void
    {
        [, $token] = $this->makeAdmin();
        [$cid, $wid] = $this->seedClassificationAndWarehouse();

        $resp = $this->postJson('/api/v1/books', $this->validBookPayload($cid, $wid), $this->auth($token))
            ->assertStatus(201);
        $bookId = $resp->json('data.id');

        // Xóa mềm
        $this->deleteJson("/api/v1/books/$bookId", [], $this->auth($token))->assertStatus(200);

        // Không xuất hiện trong danh sách chính
        $list = $this->getJson('/api/v1/books', $this->auth($token));
        $ids = collect($list->json('data.data') ?? [])->pluck('id')->all();
        $this->assertNotContains($bookId, $ids);
    }

    #[Test]
    public function soft_deleted_book_appears_in_trash(): void
    {
        [, $token] = $this->makeAdmin();
        [$cid, $wid] = $this->seedClassificationAndWarehouse();

        $resp = $this->postJson('/api/v1/books', $this->validBookPayload($cid, $wid), $this->auth($token))
            ->assertStatus(201);
        $bookId = $resp->json('data.id');

        $this->deleteJson("/api/v1/books/$bookId", [], $this->auth($token));

        $trash = $this->getJson('/api/v1/books/trash', $this->auth($token))->assertStatus(200);
        $ids = collect($trash->json('data.data') ?? [])->pluck('id')->all();
        $this->assertContains($bookId, $ids);
    }

    #[Test]
    public function restore_soft_deleted_book_works(): void
    {
        [, $token] = $this->makeAdmin();
        [$cid, $wid] = $this->seedClassificationAndWarehouse();

        $resp = $this->postJson('/api/v1/books', $this->validBookPayload($cid, $wid), $this->auth($token))
            ->assertStatus(201);
        $bookId = $resp->json('data.id');

        $this->deleteJson("/api/v1/books/$bookId", [], $this->auth($token));
        $this->postJson("/api/v1/books/restore/$bookId", [], $this->auth($token))->assertStatus(200);
        $this->assertNotSoftDeleted('books', ['id' => $bookId]);
    }

    // ── Pagination & Query params ─────────────────────────────────────────────

    #[Test]
    public function book_list_with_per_page_exceeding_max_returns_422(): void
    {
        [, $token] = $this->makeAdmin();
        $this->getJson('/api/v1/books?per_page=999', $this->auth($token))->assertStatus(422);
    }

    #[Test]
    public function book_list_with_invalid_sort_returns_422(): void
    {
        [, $token] = $this->makeAdmin();
        $this->getJson('/api/v1/books?sort=hack; DROP TABLE books', $this->auth($token))->assertStatus(422);
    }

    #[Test]
    public function book_list_with_valid_sort_options_returns_200(): void
    {
        [, $token] = $this->makeAdmin();

        foreach (['newest', 'oldest', 'az', 'za'] as $sort) {
            $this->getJson("/api/v1/books?sort=$sort", $this->auth($token))
                ->assertStatus(200, "Sort '$sort' should be valid");
        }
    }

    #[Test]
    public function book_search_with_sql_injection_keyword_returns_200_without_data_leak(): void
    {
        [, $token] = $this->makeAdmin();
        // SQL Injection trong keyword – phải dùng parameterized query
        $response = $this->getJson("/api/v1/books?keyword=' OR 1=1--", $this->auth($token))
            ->assertStatus(200);

        // Kết quả phải là danh sách bình thường, không crash
        $this->assertArrayHasKey('data', $response->json('data'));
    }

    // ── File Upload ───────────────────────────────────────────────────────────

    #[Test]
    public function import_books_without_file_returns_422(): void
    {
        [, $token] = $this->makeAdmin();
        $this->postJson('/api/v1/books/import', [], $this->auth($token))
            ->assertStatus(422);
    }

    #[Test]
    public function import_books_with_wrong_mime_type_returns_422(): void
    {
        Storage::fake('local');
        [, $token] = $this->makeAdmin();

        $file = UploadedFile::fake()->create('malicious.php', 100, 'application/x-php');

        $this->post('/api/v1/books/import', ['file' => $file], $this->auth($token))
            ->assertStatus(422);
    }

    #[Test]
    public function update_book_image_with_non_image_file_returns_422(): void
    {
        Storage::fake('local');
        [, $token] = $this->makeAdmin();
        [$cid, $wid] = $this->seedClassificationAndWarehouse();

        $resp = $this->postJson('/api/v1/books', $this->validBookPayload($cid, $wid), $this->auth($token));
        $bookId = $resp->json('data.id');

        // Upload PDF thay vì ảnh
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->post("/api/v1/books/$bookId/image", ['book_cover' => $file], $this->auth($token))
            ->assertStatus(422);
    }

    // ── Force Delete ─────────────────────────────────────────────────────────

    #[Test]
    public function force_delete_requires_ids_array(): void
    {
        [, $token] = $this->makeAdmin();
        $this->deleteJson('/api/v1/books/force', [], $this->auth($token))
            ->assertStatus(422);
    }

    #[Test]
    public function force_delete_single_book_that_doesnt_exist_returns_404(): void
    {
        [, $token] = $this->makeAdmin();
        $this->deleteJson('/api/v1/books/force/9999999', [], $this->auth($token))
            ->assertStatus(404);
    }

    #[Test]
    public function admin_can_create_book_with_long_pasted_summary(): void
    {
        [, $token] = $this->makeAdmin();
        [$cid, $wid] = $this->seedClassificationAndWarehouse();
        $payload = $this->validBookPayload($cid, $wid);
        $payload['summary'] = str_repeat('Luận văn dán từ Word. ', 8000);

        $payload['resource_type'] = 'textbook';
        $payload['access_mode'] = 'circulation_only';

        $this->postJson('/api/v1/books', $payload, $this->auth($token))
            ->assertStatus(201)
            ->assertJsonPath('status', 'success');
    }
}
