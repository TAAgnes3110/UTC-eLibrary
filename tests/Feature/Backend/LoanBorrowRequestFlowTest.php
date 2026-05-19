<?php

namespace Tests\Feature\Backend;

use App\Enums\BookStatus;
use App\Enums\LibraryCardStatus;
use App\Enums\RoleType;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Classification;
use App\Models\LibraryCard;
use App\Models\LoanBorrowRequest;
use App\Models\StorageCabinet;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsLoanPolicies;
use Tests\TestCase;

class LoanBorrowRequestFlowTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;
    use SeedsLoanPolicies;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLoanPolicies();
    }

    public function test_reader_create_request_then_admin_approve_deducts_stock_only_on_approve(): void
    {
        [$book, $cabinet] = $this->seedBookWithStorageAndCopies(3);
        [$reader, $readerToken] = $this->createUserAndToken([
            'user_type' => RoleType::STUDENT,
            'email' => 'reader-br@test.com',
        ]);
        [, $adminToken] = $this->createAdminUserAndToken();

        $card = LibraryCard::query()->create([
            'card_number' => 'SV-BR-001',
            'code' => 'SV-BR-001',
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
            'full_name' => 'Ban doc BR',
            'user_id' => $reader->id,
        ]);

        $create = $this->postJson('/api/v1/me/loan-borrow-requests', [
            'loan_type' => 'home',
            'book_ids' => [$book->id],
            'quantity' => [2],
            'requested_loan_date' => now()->toDateString(),
            'requested_due_date' => now()->addDays(7)->toDateString(),
            'request_note' => 'Em muon sach phuc vu hoc tap',
        ], $this->apiTokenHeaders($readerToken));

        $create->assertStatus(201)->assertJsonPath('status', 'success');
        $requestId = (int) $create->json('data.id');
        $itemId = (int) $create->json('data.items.0.id');

        $this->assertDatabaseHas('loan_borrow_requests', [
            'id' => $requestId,
            'status' => LoanBorrowRequest::STATUS_PENDING,
            'library_card_id' => $card->id,
        ]);
        $this->assertDatabaseHas('storage_cabinets', [
            'id' => $cabinet->id,
            'current_quantity' => 0,
        ]);

        $approve = $this->postJson("/api/v1/loans/borrow-requests/{$requestId}/approve", [
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'review_note' => 'Da kiem tra tinh trang sach',
            'condition_on_loan' => [
                $itemId => 'tot',
            ],
        ], $this->apiTokenHeaders($adminToken));

        $approve->assertStatus(200)->assertJsonPath('status', 'success');
        $this->assertDatabaseHas('loan_borrow_requests', [
            'id' => $requestId,
            'status' => LoanBorrowRequest::STATUS_APPROVED,
        ]);
        $this->assertDatabaseHas('storage_cabinets', [
            'id' => $cabinet->id,
            'current_quantity' => 1,
        ]);
        $this->assertSame(2, BookCopy::query()->where('book_id', $book->id)->where('status', BookStatus::BORROWED)->count());
    }

    public function test_admin_approve_provisions_book_copies_when_book_has_quantity_but_no_copies(): void
    {
        $warehouse = Warehouse::query()->create([
            'code' => 'KHO-LEGACY',
            'name' => 'Kho legacy',
            'is_active' => true,
        ]);
        $classification = Classification::query()->create([
            'code' => 'CL-LEGACY',
            'name' => 'Phan loai legacy',
        ]);
        $book = Book::query()->create([
            'title' => 'Sach chi co quantity khong co ban in',
            'resource_type' => 'reference',
            'access_mode' => 'circulation_only',
            'quantity' => 3,
            'classification_id' => $classification->id,
            'warehouse_id' => $warehouse->id,
        ]);

        [$reader, $readerToken] = $this->createUserAndToken([
            'user_type' => RoleType::STUDENT,
            'email' => 'reader-legacy@test.com',
        ]);
        [, $adminToken] = $this->createAdminUserAndToken();

        $card = LibraryCard::query()->create([
            'card_number' => 'SV-BR-LEGACY',
            'code' => 'SV-BR-LEGACY',
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
            'full_name' => 'Ban doc legacy',
            'user_id' => $reader->id,
        ]);

        $create = $this->postJson('/api/v1/me/loan-borrow-requests', [
            'loan_type' => 'home',
            'book_ids' => [$book->id],
            'quantity' => [1],
            'requested_loan_date' => now()->toDateString(),
            'requested_due_date' => now()->addDays(7)->toDateString(),
        ], $this->apiTokenHeaders($readerToken));
        $create->assertStatus(201);
        $requestId = (int) $create->json('data.id');

        $this->assertSame(0, BookCopy::query()->where('book_id', $book->id)->count());

        $approve = $this->postJson("/api/v1/loans/borrow-requests/{$requestId}/approve", [
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
        ], $this->apiTokenHeaders($adminToken));

        $approve->assertStatus(200)->assertJsonPath('status', 'success');
        $this->assertSame(1, BookCopy::query()->where('book_id', $book->id)->where('status', BookStatus::BORROWED)->count());
        $this->assertSame(2, BookCopy::query()->where('book_id', $book->id)->where('status', BookStatus::AVAILABLE)->count());
        $book->refresh();
        $this->assertSame(2, (int) $book->quantity);
    }

    public function test_second_reader_cannot_create_pending_request_when_only_one_copy_left_and_already_reserved(): void
    {
        [$book] = $this->seedBookWithStorageAndCopies(1);
        [$readerA, $tokenA] = $this->createUserAndToken([
            'user_type' => RoleType::STUDENT,
            'email' => 'reader-a@test.com',
        ]);
        [$readerB, $tokenB] = $this->createUserAndToken([
            'user_type' => RoleType::STUDENT,
            'email' => 'reader-b@test.com',
        ]);

        LibraryCard::query()->create([
            'card_number' => 'SV-BR-A',
            'code' => 'SV-BR-A',
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
            'full_name' => 'Ban doc A',
            'user_id' => $readerA->id,
        ]);
        LibraryCard::query()->create([
            'card_number' => 'SV-BR-B',
            'code' => 'SV-BR-B',
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
            'full_name' => 'Ban doc B',
            'user_id' => $readerB->id,
        ]);

        $first = $this->postJson('/api/v1/me/loan-borrow-requests', [
            'loan_type' => 'home',
            'book_ids' => [$book->id],
            'quantity' => [1],
            'requested_loan_date' => now()->toDateString(),
            'requested_due_date' => now()->addDays(7)->toDateString(),
        ], $this->apiTokenHeaders($tokenA));
        $first->assertStatus(201)->assertJsonPath('status', 'success');

        $second = $this->postJson('/api/v1/me/loan-borrow-requests', [
            'loan_type' => 'home',
            'book_ids' => [$book->id],
            'quantity' => [1],
            'requested_loan_date' => now()->toDateString(),
            'requested_due_date' => now()->addDays(7)->toDateString(),
        ], $this->apiTokenHeaders($tokenB));

        $second->assertStatus(422)->assertJsonPath('status', 'error');
        $this->assertStringContainsString('không đủ số lượng giữ chỗ', (string) $second->json('messages'));
    }

    public function test_admin_bulk_reject_rejects_multiple_pending_requests(): void
    {
        [$book] = $this->seedBookWithStorageAndCopies(2);
        [$readerA, $tokenA] = $this->createUserAndToken([
            'user_type' => RoleType::STUDENT,
            'email' => 'reader-bulk-a@test.com',
        ]);
        [$readerB, $tokenB] = $this->createUserAndToken([
            'user_type' => RoleType::STUDENT,
            'email' => 'reader-bulk-b@test.com',
        ]);
        [, $adminToken] = $this->createAdminUserAndToken();

        LibraryCard::query()->create([
            'card_number' => 'SV-BR-BULK-A',
            'code' => 'SV-BR-BULK-A',
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
            'full_name' => 'Ban doc bulk A',
            'user_id' => $readerA->id,
        ]);
        LibraryCard::query()->create([
            'card_number' => 'SV-BR-BULK-B',
            'code' => 'SV-BR-BULK-B',
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
            'full_name' => 'Ban doc bulk B',
            'user_id' => $readerB->id,
        ]);

        $first = $this->postJson('/api/v1/me/loan-borrow-requests', [
            'loan_type' => 'home',
            'book_ids' => [$book->id],
            'quantity' => [1],
            'requested_loan_date' => now()->toDateString(),
            'requested_due_date' => now()->addDays(7)->toDateString(),
        ], $this->apiTokenHeaders($tokenA));
        $first->assertStatus(201);
        $id1 = (int) $first->json('data.id');

        $second = $this->postJson('/api/v1/me/loan-borrow-requests', [
            'loan_type' => 'home',
            'book_ids' => [$book->id],
            'quantity' => [1],
            'requested_loan_date' => now()->toDateString(),
            'requested_due_date' => now()->addDays(7)->toDateString(),
        ], $this->apiTokenHeaders($tokenB));
        $second->assertStatus(201);
        $id2 = (int) $second->json('data.id');

        $bulk = $this->postJson('/api/v1/loans/borrow-requests/bulk-reject', [
            'ids' => [$id1, $id2],
            'review_note' => 'Tu choi hang loat test',
        ], $this->apiTokenHeaders($adminToken));

        $bulk->assertStatus(200)->assertJsonPath('status', 'success')->assertJsonPath('data.rejected_count', 2);

        $this->assertDatabaseHas('loan_borrow_requests', [
            'id' => $id1,
            'status' => LoanBorrowRequest::STATUS_REJECTED,
        ]);
        $this->assertDatabaseHas('loan_borrow_requests', [
            'id' => $id2,
            'status' => LoanBorrowRequest::STATUS_REJECTED,
        ]);
    }

    public function test_reader_can_create_onsite_request_without_requested_due_date(): void
    {
        [$book] = $this->seedBookWithStorageAndCopies(2);
        [$reader, $readerToken] = $this->createUserAndToken([
            'user_type' => RoleType::STUDENT,
            'email' => 'reader-onsite@test.com',
        ]);

        LibraryCard::query()->create([
            'card_number' => 'SV-BR-ONSITE',
            'code' => 'SV-BR-ONSITE',
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
            'full_name' => 'Ban doc onsite',
            'user_id' => $reader->id,
        ]);

        $loanDate = now()->toDateString();
        $create = $this->postJson('/api/v1/me/loan-borrow-requests', [
            'loan_type' => 'onsite',
            'book_ids' => [$book->id],
            'quantity' => [1],
            'requested_loan_date' => $loanDate,
        ], $this->apiTokenHeaders($readerToken));

        $create->assertStatus(201)->assertJsonPath('status', 'success');
        $requestId = (int) $create->json('data.id');
        $this->assertDatabaseHas('loan_borrow_requests', [
            'id' => $requestId,
            'loan_type' => 'onsite',
            'status' => LoanBorrowRequest::STATUS_PENDING,
        ]);

        [, $adminToken] = $this->createAdminUserAndToken();
        $list = $this->getJson('/api/v1/loans/borrow-requests?status=pending', $this->apiTokenHeaders($adminToken));
        $list->assertStatus(200);
        $row = collect($list->json('data.data'))->firstWhere('id', $requestId);
        $this->assertNotNull($row);
        $this->assertNull($row['requested_due_date'] ?? null);
        $this->assertSame(
            Carbon::parse($loanDate)->addDays(30)->toDateString(),
            $row['suggested_due_date'] ?? null
        );
    }

    public function test_reader_index_filters_by_status_and_respects_per_page(): void
    {
        [$book] = $this->seedBookWithStorageAndCopies(3);
        [$reader, $readerToken] = $this->createUserAndToken([
            'user_type' => RoleType::STUDENT,
            'email' => 'reader-index-filter@test.com',
        ]);

        $card = LibraryCard::query()->create([
            'card_number' => 'SV-BR-IDX',
            'code' => 'SV-BR-IDX',
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
            'full_name' => 'Ban doc index',
            'user_id' => $reader->id,
        ]);

        $first = $this->postJson('/api/v1/me/loan-borrow-requests', [
            'loan_type' => 'home',
            'book_ids' => [$book->id],
            'quantity' => [1],
            'requested_loan_date' => now()->toDateString(),
            'requested_due_date' => now()->addDays(7)->toDateString(),
        ], $this->apiTokenHeaders($readerToken));
        $first->assertStatus(201);

        LoanBorrowRequest::query()->create([
            'request_code' => 'BRTESTIDX001',
            'library_card_id' => $card->id,
            'requested_by' => $reader->id,
            'loan_type' => 'home',
            'status' => LoanBorrowRequest::STATUS_REJECTED,
        ]);

        $response = $this->getJson('/api/v1/me/loan-borrow-requests?status=pending&per_page=1', $this->apiTokenHeaders($readerToken));
        $response->assertStatus(200)->assertJsonPath('status', 'success');

        $items = (array) $response->json('data.data');
        $this->assertCount(1, $items);
        $this->assertSame(LoanBorrowRequest::STATUS_PENDING, (string) ($items[0]['status'] ?? ''));
    }

    /**
     * @return array{0:Book,1:StorageCabinet}
     */
    private function seedBookWithStorageAndCopies(int $copyCount): array
    {
        $warehouse = Warehouse::query()->create([
            'code' => 'KHO-BR',
            'name' => 'Kho borrow request',
            'is_active' => true,
        ]);
        $classification = Classification::query()->create([
            'code' => 'CL-BR',
            'name' => 'Phan loai borrow request',
        ]);
        $cabinet = StorageCabinet::query()->create([
            'warehouse_id' => $warehouse->id,
            'classification_id' => $classification->id,
            'code' => 'TU-BR-01',
            'name' => 'Tu borrow request',
            'current_quantity' => 0,
            'is_active' => true,
        ]);
        $book = Book::query()->create([
            'title' => 'Sach test yeu cau muon',
            'resource_type' => 'textbook',
            'access_mode' => 'circulation_only',
            'quantity' => $copyCount,
            'classification_id' => $classification->id,
            'warehouse_id' => $warehouse->id,
            'cabinet' => $cabinet->name,
        ]);

        for ($i = 1; $i <= $copyCount; $i++) {
            BookCopy::query()->create([
                'book_id' => $book->id,
                'barcode' => sprintf('BC-BR-%03d', $i),
                'status' => BookStatus::AVAILABLE->value,
                'physical_condition' => 'good',
                'warehouse_id' => $warehouse->id,
            ]);
        }

        return [$book, $cabinet];
    }
}
