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
        $classificationDetailId = DB::table('classification_details')->insertGetId([
            'code' => 'CD-TEST-001',
            'name' => 'Phân loại chi tiết test',
            'classification_id' => $classificationId,
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

        return [$classificationId, $classificationDetailId, $warehouseId];
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
        [$classificationId, $classificationDetailId, $warehouseId] = $this->makeBookDeps();

        $responseLongTitle = $this->postJson('/api/v1/books', [
            'title' => str_repeat('A', 300),
            'warehouse_id' => $warehouseId,
            'quantity' => 1,
            'classification_id' => $classificationId,
            'classification_detail_id' => $classificationDetailId,
        ], $this->apiTokenHeaders($token));
        $responseLongTitle->assertStatus(422);

        $responseNegativeQty = $this->postJson('/api/v1/books', [
            'title' => 'Sách test',
            'warehouse_id' => $warehouseId,
            'quantity' => -1,
            'classification_id' => $classificationId,
            'classification_detail_id' => $classificationDetailId,
        ], $this->apiTokenHeaders($token));
        $responseNegativeQty->assertStatus(422);

        $responseBadEnum = $this->postJson('/api/v1/books', [
            'title' => 'Sách test',
            'warehouse_id' => $warehouseId,
            'quantity' => 2,
            'classification_id' => $classificationId,
            'classification_detail_id' => $classificationDetailId,
            'resource_type' => 'super_invalid_type',
        ], $this->apiTokenHeaders($token));
        $responseBadEnum->assertStatus(422);
    }

    public function test_books_update_persists_multiple_authors_and_publishers_split_by_separators(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        [$classificationId, $classificationDetailId, $warehouseId] = $this->makeBookDeps();

        $bookId = DB::table('books')->insertGetId([
            'title' => 'Sách ban đầu',
            'warehouse_id' => $warehouseId,
            'classification_id' => $classificationId,
            'classification_detail_id' => $classificationDetailId,
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
        [$classificationId, $classificationDetailId, $warehouseId] = $this->makeBookDeps();

        $bookId = DB::table('books')->insertGetId([
            'title' => 'Sách update giới hạn',
            'warehouse_id' => $warehouseId,
            'classification_id' => $classificationId,
            'classification_detail_id' => $classificationDetailId,
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
}

