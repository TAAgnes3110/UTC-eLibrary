<?php

namespace Tests\Feature\Backend;

use App\Enums\BookStatus;
use App\Enums\LibraryCardStatus;
use App\Enums\NotificationType;
use App\Enums\RoleType;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Classification;
use App\Models\LibraryCard;
use App\Models\Loan;
use App\Models\Notification as LibraryNotification;
use App\Models\StorageCabinet;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\SeedsLoanPolicies;
use Tests\TestCase;

class LoanDeleteAndDueSoonNotificationTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;
    use SeedsLoanPolicies;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLoanPolicies();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_cannot_delete_open_loan(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        [$book] = $this->seedBookWithStorageAndCopies(1);
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

        $del = $this->deleteJson("/api/v1/loans/{$loanId}", [], $this->apiTokenHeaders($token));
        $del->assertStatus(422)->assertJsonPath('messages', 'Chỉ được xóa phiếu ở trạng thái đã trả.');
    }

    public function test_delete_returned_loan_sets_deleted_flag(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        [$book] = $this->seedBookWithStorageAndCopies(1);
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

        $this->postJson("/api/v1/loans/{$loanId}/return", [
            'return_date' => now()->addDay()->toDateString(),
            'condition_on_return' => 'tot',
        ], $this->apiTokenHeaders($token))->assertStatus(200);

        $del = $this->deleteJson("/api/v1/loans/{$loanId}", [], $this->apiTokenHeaders($token));
        $del->assertStatus(200)->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('loans', [
            'id' => $loanId,
            'deleted' => 1,
        ]);
        $this->assertNull(Loan::query()->whereKey($loanId)->first());
    }

    public function test_notify_due_soon_creates_user_and_admin_notifications(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-26 08:00:00', 'UTC'));

        [$admin] = $this->createAdminUserAndToken();
        $reader = User::factory()->create([
            'user_type' => RoleType::STUDENT,
            'email' => 'reader-due-soon@test.com',
        ]);

        $card = LibraryCard::query()->create([
            'card_number' => 'SV-DUE-SOON-1',
            'code' => 'SV-DUE-SOON-1',
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
            'full_name' => 'Ban doc due soon',
            'user_id' => $reader->id,
        ]);

        Loan::query()->create([
            'loan_code' => 'LDUESOON1',
            'library_card_id' => $card->id,
            'loan_type' => Loan::TYPE_HOME,
            'loan_date' => '2026-04-20',
            'due_date' => '2026-04-28',
            'return_date' => null,
            'status' => Loan::STATUS_BORROWED,
            'deleted' => false,
        ]);

        $this->artisan('loans:notify-due-soon')->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'recipient_type' => LibraryNotification::RECIPIENT_USER,
            'recipient_id' => $reader->id,
            'type' => NotificationType::USER_LOAN_DUE_SOON_REMINDER->value,
        ]);

        $this->assertDatabaseHas('notifications', [
            'recipient_type' => LibraryNotification::RECIPIENT_ADMIN,
            'recipient_id' => $admin->id,
            'type' => NotificationType::ADMIN_LOAN_DUE_SOON_DIGEST->value,
        ]);
    }

    /**
     * @return array{0: Book, 1: StorageCabinet}
     */
    private function seedBookWithStorageAndCopies(int $copyCount): array
    {
        $warehouse = Warehouse::query()->create([
            'code' => 'KHO-LD',
            'name' => 'Kho test xoa phieu',
            'is_active' => true,
        ]);
        $classification = Classification::query()->create([
            'code' => 'CL-LD',
            'name' => 'Phan loai test xoa phieu',
        ]);
        $cabinet = StorageCabinet::query()->create([
            'warehouse_id' => $warehouse->id,
            'classification_id' => $classification->id,
            'code' => 'TU-LD-01',
            'name' => 'Tu test xoa phieu',
            'current_quantity' => 0,
            'is_active' => true,
        ]);
        $book = Book::query()->create([
            'title' => 'Sach test xoa phieu',
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
                'barcode' => sprintf('BC-LD-%03d', $i),
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
            'card_number' => 'SV-LOAN-DEL-001',
            'code' => 'SV-LOAN-DEL-001',
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
            'full_name' => 'Ban doc test xoa phieu',
        ]);
    }
}
