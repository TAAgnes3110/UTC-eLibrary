<?php

namespace Tests\Feature\Backend;

use App\Enums\BookStatus;
use App\Enums\LibraryCardStatus;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Classification;
use App\Models\LibraryCard;
use App\Models\StorageCabinet;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsLoanPolicies;
use Tests\TestCase;

class LoanStorageQuantitySyncTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;
    use SeedsLoanPolicies;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLoanPolicies();
    }

    public function test_create_loan_decrements_slot_and_cabinet_current_quantity(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        [$book, $cabinet] = $this->seedBookWithStorageAndCopies(2);
        $card = $this->createActiveStudentCard();

        $response = $this->postJson('/api/v1/loans', [
            'library_card_id' => $card->id,
            'loan_type' => 'home',
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'da_muon',
            'book_ids' => [$book->id],
            'quantity' => [1],
            'condition_on_loan' => ['tot'],
        ], $this->apiTokenHeaders($token));

        $response->assertStatus(201)->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('book_copies', [
            'book_id' => $book->id,
            'status' => BookStatus::BORROWED->value,
        ]);
        $this->assertDatabaseHas('storage_cabinets', [
            'id' => $cabinet->id,
            'current_quantity' => 1,
        ]);
    }

    public function test_return_loan_restocks_slot_and_cabinet_current_quantity(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        [$book, $cabinet] = $this->seedBookWithStorageAndCopies(2);
        $card = $this->createActiveStudentCard();

        $createResponse = $this->postJson('/api/v1/loans', [
            'library_card_id' => $card->id,
            'loan_type' => 'home',
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'da_muon',
            'book_ids' => [$book->id],
            'quantity' => [1],
            'condition_on_loan' => ['tot'],
        ], $this->apiTokenHeaders($token));
        $createResponse->assertStatus(201);
        $loanId = (int) $createResponse->json('data.id');

        $returnResponse = $this->postJson("/api/v1/loans/{$loanId}/return", [
            'return_date' => now()->addDays(2)->toDateString(),
            'condition_on_return' => 'tot',
        ], $this->apiTokenHeaders($token));

        $returnResponse->assertStatus(200)->assertJsonPath('status', 'success');
        $this->assertSame(0, BookCopy::query()->where('book_id', $book->id)->where('status', BookStatus::BORROWED)->count());
        $this->assertDatabaseHas('storage_cabinets', [
            'id' => $cabinet->id,
            'current_quantity' => 2,
        ]);
    }

    /**
     * @return array{0:Book,1:StorageCabinet}
     */
    private function seedBookWithStorageAndCopies(int $copyCount): array
    {
        $warehouse = Warehouse::query()->create([
            'code' => 'KHO-LS',
            'name' => 'Kho luu thong',
            'is_active' => true,
        ]);
        $classification = Classification::query()->create([
            'code' => 'CL-LS',
            'name' => 'Phan loai test',
        ]);
        $cabinet = StorageCabinet::query()->create([
            'warehouse_id' => $warehouse->id,
            'classification_id' => $classification->id,
            'code' => 'TU-LS-01',
            'name' => 'Tu test',
            'current_quantity' => 0,
            'is_active' => true,
        ]);
        $book = Book::query()->create([
            'title' => 'Sach test muon tra',
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
                'barcode' => sprintf('BC-LS-%03d', $i),
                'status' => BookStatus::AVAILABLE->value,
                'physical_condition' => 'good',
                'warehouse_id' => $warehouse->id,
            ]);
        }

        return [$book, $cabinet];
    }

    private function createActiveStudentCard(): LibraryCard
    {
        return LibraryCard::query()->create([
            'card_number' => 'SV-LOAN-001',
            'code' => 'SV-LOAN-001',
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
            'full_name' => 'Ban doc test',
        ]);
    }
}
