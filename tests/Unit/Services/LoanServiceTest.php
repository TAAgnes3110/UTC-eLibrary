<?php

namespace Tests\Unit\Services;

use App\Enums\BookStatus;
use App\Enums\LibraryCardStatus;
use App\Enums\RoleType;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\LibraryCard;
use App\Models\Loan;
use App\Models\LoanPolicy;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\LoanService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_reader_cannot_borrow_for_home(): void
    {
        $user = User::factory()->create(['user_type' => RoleType::STUDENT]);
        LibraryCard::query()->create([
            'user_id' => $user->id,
            'card_number' => 'CARD-EXT-'.uniqid(),
            'holder_type' => LibraryCard::HOLDER_TYPE_EXTERNAL,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
        ]);

        $this->expectException(AuthorizationException::class);
        app(LoanService::class)->assertCanBorrowForHome($user);
    }

    public function test_card_code_may_differ_from_user_code_without_failing_validation(): void
    {
        $this->expectNotToPerformAssertions();

        $user = User::factory()->create(['code' => '001112223334', 'user_type' => RoleType::STUDENT]);
        LibraryCard::query()->create([
            'user_id' => $user->id,
            'card_number' => 'CARD-001',
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'code' => '888877776666',
            'expiry_date' => now()->addYear()->toDateString(),
            'status' => LibraryCardStatus::ACTIVE,
        ]);

        app(LoanService::class)->assertReaderCardForLoan($user->fresh('libraryCard'));
    }

    public function test_calculate_overdue_fine_uses_policy_per_day(): void
    {
        $policy = LoanPolicy::query()->create([
            'code' => 'T',
            'name' => 'Test',
            'user_type' => 'MEMBER',
            'max_books' => 5,
            'max_days' => 14,
            'max_renewals' => 0,
            'overdue_fine_per_day' => 3000,
            'allow_home' => true,
            'allow_onsite' => true,
        ]);

        $loan = new Loan([
            'due_date' => now()->subDays(3)->toDateString(),
            'return_date' => null,
        ]);
        $loan->setRelation('policy', $policy);

        $fine = app(LoanService::class)->calculateOverdueFine($loan, now());
        $this->assertSame('9000.00', $fine);
    }

    public function test_create_home_borrow_and_return_round_trip(): void
    {
        $warehouse = Warehouse::query()->create([
            'code' => 'TVTT',
            'name' => 'Kho test',
            'is_active' => true,
        ]);

        $book = Book::query()->create([
            'title' => 'Sách test',
            'quantity' => 1,
            'warehouse_id' => $warehouse->id,
        ]);

        $copy = BookCopy::query()->create([
            'book_id' => $book->id,
            'barcode' => 'CPY-TEST-1',
            'status' => BookStatus::AVAILABLE,
            'warehouse_id' => $warehouse->id,
        ]);

        LoanPolicy::query()->create([
            'code' => 'MEMBER',
            'name' => 'Sinh viên',
            'user_type' => 'MEMBER',
            'max_books' => 5,
            'max_days' => 7,
            'max_renewals' => 0,
            'overdue_fine_per_day' => 1000,
            'allow_home' => true,
            'allow_onsite' => true,
        ]);

        $borrower = User::factory()->create(['code' => '009998887761', 'user_type' => RoleType::STUDENT]);
        LibraryCard::query()->create([
            'user_id' => $borrower->id,
            'card_number' => 'UTC-BORROW-01',
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'code' => '009998887761',
            'expiry_date' => now()->addYear()->toDateString(),
            'status' => LibraryCardStatus::ACTIVE,
        ]);

        $librarian = User::factory()->create(['user_type' => RoleType::LIBRARIAN]);

        $service = app(LoanService::class);
        $loan = $service->createHomeBorrow($borrower->fresh(['libraryCard']), $copy->fresh(), $librarian);

        $this->assertSame(LoanService::LOAN_STATUS_ACTIVE, $loan->status);
        $this->assertSame(BookStatus::BORROWED, $copy->fresh()->status);

        $returned = $service->returnHomeLoan($loan->fresh(['bookCopy', 'policy']));
        $this->assertSame(LoanService::LOAN_STATUS_RETURNED, $returned->status);
        $this->assertNotNull($returned->return_date);
        $this->assertSame(BookStatus::AVAILABLE, $copy->fresh()->status);
    }
}
