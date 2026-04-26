<?php

namespace Tests\Feature\Backend;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApiRobustnessTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    private function makeBookDeps(): array
    {
        $now = now();
        $classificationId = DB::table('classifications')->insertGetId([
            'code' => 'C-TEST-001',
            'name' => 'Phân loại test',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $warehouseId = DB::table('warehouses')->insertGetId([
            'code' => 'WH-TEST-001',
            'name' => 'Kho test',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [$classificationId, $warehouseId];
    }

    public function test_books_index_rejects_too_large_per_page(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson('/api/v1/books?per_page=999999', $this->apiTokenHeaders($token));

        $response->assertStatus(422);
    }

    public function test_books_index_handles_huge_keyword_without_500(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        $hugeKeyword = str_repeat('abcXYZ123 ', 3000);

        $response = $this->getJson('/api/v1/books?keyword='.urlencode($hugeKeyword).'&per_page=50', $this->apiTokenHeaders($token));

        $this->assertContains($response->status(), [200, 422]);
        $this->assertNotEquals(500, $response->status());
    }

    public function test_books_store_validates_invalid_payload_variants(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        [$classificationId, $warehouseId] = $this->makeBookDeps();

        $responseLongTitle = $this->postJson('/api/v1/books', [
            'title' => str_repeat('A', 300),
            'warehouse_id' => $warehouseId,
            'quantity' => 1,
            'classification_id' => $classificationId,
        ], $this->apiTokenHeaders($token));
        $responseLongTitle->assertStatus(422);

        $responseNegativeQty = $this->postJson('/api/v1/books', [
            'title' => 'Sách test',
            'warehouse_id' => $warehouseId,
            'quantity' => -1,
            'classification_id' => $classificationId,
        ], $this->apiTokenHeaders($token));
        $responseNegativeQty->assertStatus(422);

        $responseBadEnum = $this->postJson('/api/v1/books', [
            'title' => 'Sách test',
            'warehouse_id' => $warehouseId,
            'quantity' => 2,
            'classification_id' => $classificationId,
            'resource_type' => 'super_invalid_type',
        ], $this->apiTokenHeaders($token));
        $responseBadEnum->assertStatus(422);
    }

    public function test_books_update_persists_multiple_authors_and_publishers_split_by_separators(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        [$classificationId, $warehouseId] = $this->makeBookDeps();

        $bookId = DB::table('books')->insertGetId([
            'title' => 'Sách ban đầu',
            'warehouse_id' => $warehouseId,
            'classification_id' => $classificationId,
            'quantity' => 5,
            'resource_type' => 'reference',
            'access_mode' => 'circulation_only',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->putJson("/api/v1/books/{$bookId}", [
            'title' => 'Sách cập nhật',
            'warehouse_id' => $warehouseId,
            'quantity' => 7,
            'authors' => 'Nguyễn A; Trần B, Lê C',
            'publisher' => 'NXB 1; NXB 2, NXB 3',
        ], $this->apiTokenHeaders($token));

        $response->assertStatus(200);

        /** @var Book $book */
        $book = Book::query()->findOrFail($bookId);
        $authors = $book->authors()->orderBy('book_authors.order')->pluck('name')->all();
        $publishers = $book->publishers()->orderBy('book_publishers.order')->pluck('name')->all();

        $this->assertSame(['Nguyễn A', 'Trần B', 'Lê C'], $authors);
        $this->assertSame(['NXB 1', 'NXB 2', 'NXB 3'], $publishers);
    }

    public function test_books_update_rejects_overly_large_authors_and_publishers_fields(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        [$classificationId, $warehouseId] = $this->makeBookDeps();

        $bookId = DB::table('books')->insertGetId([
            'title' => 'Sách update giới hạn',
            'warehouse_id' => $warehouseId,
            'classification_id' => $classificationId,
            'quantity' => 3,
            'resource_type' => 'reference',
            'access_mode' => 'circulation_only',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->putJson("/api/v1/books/{$bookId}", [
            'title' => 'Sách update giới hạn',
            'warehouse_id' => $warehouseId,
            'quantity' => 3,
            'authors' => str_repeat('A', 2500),
            'publisher' => str_repeat('P', 1500),
        ], $this->apiTokenHeaders($token));

        $response->assertStatus(422);
    }

    public function test_books_store_handles_extremely_large_input_without_500(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        [$classificationId, $warehouseId] = $this->makeBookDeps();

        $payload = [
            'title' => str_repeat('SACH-RAT-LON ', 5000),
            'warehouse_id' => $warehouseId,
            'quantity' => 1,
            'classification_id' => $classificationId,
            'authors' => str_repeat('TacGiaLon+', 400),
            'publisher' => str_repeat('NXB-LON+', 300),
            'summary' => str_repeat('Noi dung rat lon ', 8000),
            'published_year' => now()->year,
            'resource_type' => 'textbook',
        ];

        $response = $this->postJson('/api/v1/books', $payload, $this->apiTokenHeaders($token));

        $this->assertContains($response->status(), [200, 201, 422]);
        $this->assertNotEquals(500, $response->status());
    }

    public function test_books_store_accepts_mixed_plus_english_vietnamese_characters(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        [$classificationId, $warehouseId] = $this->makeBookDeps();

        $response = $this->postJson('/api/v1/books', [
            'title' => 'Cấu trúc dữ liệu + Data Structures + nâng cao',
            'warehouse_id' => $warehouseId,
            'quantity' => 2,
            'classification_id' => $classificationId,
            'authors' => 'Nguyễn Văn A + John Smith',
            'publisher' => 'NXB Giáo Dục + Global Press',
            'summary' => 'Nội dung test xen kẽ English + Tiếng Việt + ký tự +++',
            'published_year' => now()->year,
            'resource_type' => 'reference',
        ], $this->apiTokenHeaders($token));

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('books', [
            'title' => 'Cấu trúc dữ liệu + Data Structures + nâng cao',
            'resource_type' => 'reference',
        ]);
    }

    public function test_books_store_rejects_arbitrary_large_payload_with_future_year(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        [$classificationId, $warehouseId] = $this->makeBookDeps();
        $futureYear = (int) now()->year + 5;

        $response = $this->postJson('/api/v1/books', [
            'title' => str_repeat('Du lieu bat ky ', 200),
            'warehouse_id' => $warehouseId,
            'quantity' => 99999,
            'classification_id' => $classificationId,
            'authors' => str_repeat('A+B C, ', 120),
            'publisher' => str_repeat('PUB+VN+EN ', 100),
            'summary' => str_repeat('random payload ', 1200),
            'notes' => str_repeat('ghi chu ', 900),
            'book_code' => 'BK+VN+EN-'.str_repeat('X', 40),
            'registration_number' => 'REG+'.str_repeat('9', 40),
            'published_year' => $futureYear,
            'resource_type' => 'digital',
            'price' => 999999999,
            'pages' => 50000,
        ], $this->apiTokenHeaders($token));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['published_year']);
    }
}

