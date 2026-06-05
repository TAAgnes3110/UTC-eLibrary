<?php

namespace Tests\Feature\Backend;

use App\Enums\BookStatus;
use App\Enums\LibraryCardStatus;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Classification;
use App\Models\LibraryCard;
use App\Models\LoanItem;
use App\Models\StorageCabinet;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsLoanPolicies;
use Tests\TestCase;

class LoanReturnDamageFineTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;
    use SeedsLoanPolicies;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLoanPolicies();
    }

    public function test_return_damaged_book_calculates_fine_by_damage_percent(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        [, $loanId, $itemId] = $this->seedOpenLoanWithPricedBook(100_000, $token);

        $response = $this->postJson("/api/v1/loans/{$loanId}/return", [
            'return_date' => now()->toDateString(),
            'returns' => [
                (string) $itemId => [
                    'condition_on_return' => 'hong',
                    'damage_percent' => 50,
                    'fine_amount' => 0,
                ],
            ],
        ], $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('loan_items', [
            'id' => $itemId,
            'condition_on_return' => 'hong',
            'damage_percent' => 50,
            'fine_amount' => 50_000,
        ]);
    }

    public function test_return_lost_book_sets_damage_percent_to_100_and_loss_formula(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        [, $loanId, $itemId] = $this->seedOpenLoanWithPricedBook(100_000, $token);

        $response = $this->postJson("/api/v1/loans/{$loanId}/return", [
            'return_date' => now()->toDateString(),
            'returns' => [
                (string) $itemId => [
                    'condition_on_return' => 'mat',
                    'fine_amount' => 0,
                ],
            ],
        ], $this->apiTokenHeaders($token));

        $response->assertStatus(200);

        $this->assertDatabaseHas('loan_items', [
            'id' => $itemId,
            'condition_on_return' => 'mat',
            'damage_percent' => 100,
            'fine_amount' => 210_000,
        ]);
    }

    public function test_loan_detail_includes_fine_policy_snapshot(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        [, $loanId] = $this->seedOpenLoanWithPricedBook(50_000, $token);

        $response = $this->getJson("/api/v1/loans/{$loanId}", $this->apiTokenHeaders($token));

        $response->assertStatus(200)
            ->assertJsonPath('data.fine_policy.damage_fine_percent', 0.1)
            ->assertJsonPath('data.loan_items.0.book_price', 50_000);
    }

    /**
     * @return array{0: Book, 1: int, 2: int}
     */
    private function seedOpenLoanWithPricedBook(int $price, string $token): array
    {
        $book = $this->seedBookWithPrice($price);
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

        $loanId = (int) $createResponse->json('data.id');
        $itemId = (int) LoanItem::query()->where('loan_id', $loanId)->value('id');

        return [$book, $loanId, $itemId];
    }

    private function seedBookWithPrice(int $price): Book
    {
        $warehouse = Warehouse::query()->create([
            'code' => 'KHO-FINE',
            'name' => 'Kho phat test',
            'is_active' => true,
        ]);
        $classification = Classification::query()->create([
            'code' => 'CL-FINE',
            'name' => 'Phan loai phat',
        ]);
        StorageCabinet::query()->create([
            'warehouse_id' => $warehouse->id,
            'classification_id' => $classification->id,
            'code' => 'TU-FINE-01',
            'name' => 'Tu phat',
            'current_quantity' => 0,
            'is_active' => true,
        ]);
        $book = Book::query()->create([
            'title' => 'Sach tinh phat',
            'resource_type' => 'textbook',
            'access_mode' => 'circulation_only',
            'quantity' => 1,
            'price' => $price,
            'classification_id' => $classification->id,
            'warehouse_id' => $warehouse->id,
            'cabinet' => 'Tu phat',
        ]);
        BookCopy::query()->create([
            'book_id' => $book->id,
            'barcode' => 'BC-FINE-001',
            'status' => BookStatus::AVAILABLE->value,
            'physical_condition' => 'good',
            'warehouse_id' => $warehouse->id,
        ]);

        return $book;
    }

    private function createActiveStudentCard(): LibraryCard
    {
        return LibraryCard::query()->create([
            'card_number' => 'SV-FINE-001',
            'code' => 'SV-FINE-001',
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
            'full_name' => 'Ban doc phat',
        ]);
    }
}
