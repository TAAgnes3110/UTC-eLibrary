<?php

namespace Tests\Feature\Backend;

use App\Enums\BookStatus;
use App\Enums\LibraryCardStatus;
use App\Enums\LoanStatus;
use App\Enums\LoanType;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Classification;
use App\Models\LibraryCard;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\StorageCabinet;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsLoanPolicies;
use Tests\TestCase;

class LoanBorrowEligibilityTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;
    use SeedsLoanPolicies;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLoanPolicies();
    }

    public function test_cannot_create_home_loan_for_external_reader(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        $book = $this->seedBorrowableBook();
        $card = $this->createCard([
            'card_number' => 'EXT-BORROW-001',
            'code' => 'EXT-BORROW-001',
            'holder_type' => LibraryCard::HOLDER_TYPE_EXTERNAL,
        ]);

        $response = $this->postJson('/api/v1/loans', $this->loanPayload($card, $book, 'home'), $this->apiTokenHeaders($token));

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('messages', 'Loại thẻ này không được mượn tài liệu về nhà');
    }

    public function test_cannot_create_loan_when_card_workflow_not_active(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        $book = $this->seedBorrowableBook();
        $card = $this->createCard([
            'card_number' => 'PEND-BORROW-001',
            'code' => 'PEND-BORROW-001',
            'workflow_status' => LibraryCard::WORKFLOW_PENDING_REVIEW,
            'status' => LibraryCardStatus::PENDING,
        ]);

        $response = $this->postJson('/api/v1/loans', $this->loanPayload($card, $book), $this->apiTokenHeaders($token));

        $response->assertStatus(422)
            ->assertJsonPath('messages', 'Thẻ chưa ở trạng thái được phép mượn (chưa kích hoạt hoặc đã ngưng).');
    }

    public function test_cannot_create_loan_when_card_expired(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        $book = $this->seedBorrowableBook();
        $card = $this->createCard([
            'card_number' => 'EXP-BORROW-001',
            'code' => 'EXP-BORROW-001',
            'expiry_date' => now()->subDay()->toDateString(),
        ]);

        $response = $this->postJson('/api/v1/loans', $this->loanPayload($card, $book), $this->apiTokenHeaders($token));

        $response->assertStatus(422)
            ->assertJsonPath('messages', 'Thẻ thư viện đã hết hạn, không thể mượn thêm');
    }

    public function test_cannot_create_loan_when_open_loan_overdue_over_30_days(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        $book = $this->seedBorrowableBook();
        $card = $this->createCard([
            'card_number' => 'OD-BORROW-001',
            'code' => 'OD-BORROW-001',
        ]);

        $oldLoan = Loan::query()->create([
            'loan_code' => 'L-OLD-OD',
            'library_card_id' => $card->id,
            'loan_type' => LoanType::HOME,
            'loan_date' => now()->subDays(45)->toDateString(),
            'due_date' => now()->subDays(35)->toDateString(),
            'status' => LoanStatus::OVERDUE,
            'deleted' => false,
        ]);
        LoanItem::query()->create([
            'loan_id' => $oldLoan->id,
            'book_id' => $book->id,
            'quantity' => 1,
            'condition_on_loan' => 'tot',
        ]);

        $anotherBook = $this->seedBorrowableBook('Sach khac', 'BC-OD-002', 2);
        $response = $this->postJson('/api/v1/loans', $this->loanPayload($card, $anotherBook), $this->apiTokenHeaders($token));

        $response->assertStatus(422)
            ->assertJsonPath('messages', 'Thẻ có phiếu mượn quá hạn trên 30 ngày chưa trả, không thể mượn thêm');
    }

    public function test_lookup_for_loan_rejects_expired_card(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        LibraryCard::query()->create([
            'card_number' => 'EXP-LOOKUP-001',
            'code' => 'EXP-LOOKUP-001',
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
            'expiry_date' => now()->subDay()->toDateString(),
        ]);

        $response = $this->getJson(
            '/api/v1/library-cards/lookup-for-loan?card_number='.rawurlencode('EXP-LOOKUP-001'),
            $this->apiTokenHeaders($token)
        );

        $response->assertStatus(422)->assertJsonPath('status', 'error');
    }

    public function test_return_damaged_without_damage_percent_returns_422_not_500(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        [$book, $loanId, $itemId] = $this->seedOpenLoan();

        $response = $this->postJson("/api/v1/loans/{$loanId}/return", [
            'return_date' => now()->toDateString(),
            'returns' => [
                (string) $itemId => [
                    'condition_on_return' => 'hong',
                    'fine_amount' => 0,
                ],
            ],
        ], $this->apiTokenHeaders($token));

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('messages', 'Vui lòng nhập % hư hỏng (1–100) khi sách hư hỏng.');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCard(array $overrides = []): LibraryCard
    {
        return LibraryCard::query()->create(array_merge([
            'card_number' => 'SV-ELIG-'.uniqid(),
            'code' => 'SV-ELIG-'.uniqid(),
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
            'full_name' => 'Ban doc test',
        ], $overrides));
    }

    private function seedBorrowableBook(
        string $title = 'Sach test muon',
        string $barcode = 'BC-ELIG-001',
        int $quantity = 1
    ): Book {
        $warehouse = Warehouse::query()->create([
            'code' => 'KHO-ELIG-'.uniqid(),
            'name' => 'Kho test',
            'is_active' => true,
        ]);
        $classification = Classification::query()->create([
            'code' => 'CL-ELIG-'.uniqid(),
            'name' => 'Phan loai test',
        ]);
        StorageCabinet::query()->create([
            'warehouse_id' => $warehouse->id,
            'classification_id' => $classification->id,
            'code' => 'TU-ELIG-'.uniqid(),
            'name' => 'Tu test',
            'current_quantity' => 0,
            'is_active' => true,
        ]);
        $book = Book::query()->create([
            'title' => $title,
            'resource_type' => 'textbook',
            'access_mode' => 'circulation_only',
            'quantity' => $quantity,
            'classification_id' => $classification->id,
            'warehouse_id' => $warehouse->id,
            'cabinet' => 'Tu test',
        ]);
        BookCopy::query()->create([
            'book_id' => $book->id,
            'barcode' => $barcode,
            'status' => BookStatus::AVAILABLE->value,
            'physical_condition' => 'good',
            'warehouse_id' => $warehouse->id,
        ]);

        return $book;
    }

    /**
     * @return array<string, mixed>
     */
    private function loanPayload(LibraryCard $card, Book $book, string $loanType = 'home'): array
    {
        return [
            'library_card_id' => $card->id,
            'loan_type' => $loanType,
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'da_muon',
            'book_ids' => [$book->id],
            'quantity' => [1],
            'condition_on_loan' => ['tot'],
        ];
    }

    /**
     * @return array{0: Book, 1: int, 2: int}
     */
    private function seedOpenLoan(): array
    {
        $book = $this->seedBorrowableBook('Sach tra test', 'BC-RET-001');
        $card = $this->createCard([
            'card_number' => 'SV-RET-ELIG',
            'code' => 'SV-RET-ELIG',
        ]);
        [, $token] = $this->createAdminUserAndToken();

        $createResponse = $this->postJson(
            '/api/v1/loans',
            $this->loanPayload($card, $book),
            $this->apiTokenHeaders($token)
        );
        $loanId = (int) $createResponse->json('data.id');
        $itemId = (int) LoanItem::query()->where('loan_id', $loanId)->value('id');

        return [$book, $loanId, $itemId];
    }
}
