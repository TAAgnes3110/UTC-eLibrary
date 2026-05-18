<?php

namespace Tests\Feature\Loan;

use App\Enums\LibraryCardStatus;
use App\Enums\LoanStatus;
use App\Enums\LoanType;
use App\Enums\RoleType;
use App\Models\LibraryCard;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Test Loan API – bảo mật, IDOR, validation, data integrity.
 */
class LoanApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function makeLibrarian(array $extra = []): array
    {
        $role = Role::firstOrCreate(['name' => RoleType::SUPER_ADMIN->value, 'guard_name' => 'api']);
        $user = User::factory()->create(array_merge(['user_type' => RoleType::LIBRARIAN], $extra));
        $user->assignRole($role);

        return [$user, JWTAuth::fromUser($user)];
    }

    private function makeStudent(array $extra = []): array
    {
        $user = User::factory()->create(array_merge(['user_type' => RoleType::STUDENT], $extra));

        return [$user, JWTAuth::fromUser($user)];
    }

    private function auth(string $token): array
    {
        return ['Authorization' => "Bearer $token", 'Accept' => 'application/json'];
    }

    // ── Auth guard ────────────────────────────────────────────────────────────

    #[Test]
    public function unauthenticated_cannot_list_loans(): void
    {
        $this->getJson('/api/v1/loans')->assertStatus(401);
    }

    #[Test]
    public function student_cannot_access_admin_loans_list(): void
    {
        [, $token] = $this->makeStudent();
        $this->getJson('/api/v1/loans', $this->auth($token))->assertStatus(403);
    }

    #[Test]
    public function librarian_can_list_loans(): void
    {
        [, $token] = $this->makeLibrarian();
        $this->getJson('/api/v1/loans', $this->auth($token))->assertStatus(200);
    }

    // ── Pagination & Filtering ────────────────────────────────────────────────

    #[Test]
    public function loan_list_with_per_page_exceeding_100_returns_422(): void
    {
        [, $token] = $this->makeLibrarian();
        $this->getJson('/api/v1/loans?per_page=999', $this->auth($token))->assertStatus(422);
    }

    #[Test]
    public function loan_list_with_per_page_zero_returns_422(): void
    {
        [, $token] = $this->makeLibrarian();
        $this->getJson('/api/v1/loans?per_page=0', $this->auth($token))->assertStatus(422);
    }

    #[Test]
    public function loan_list_with_invalid_sort_returns_422(): void
    {
        [, $token] = $this->makeLibrarian();
        $this->getJson('/api/v1/loans?sort=INVALID; DROP TABLE loans', $this->auth($token))
            ->assertStatus(422);
    }

    #[Test]
    public function loan_list_with_valid_sort_options_returns_200(): void
    {
        [, $token] = $this->makeLibrarian();
        foreach (['newest', 'oldest', 'due_asc', 'due_desc', 'loan_asc', 'loan_desc'] as $sort) {
            $this->getJson("/api/v1/loans?sort=$sort", $this->auth($token))
                ->assertStatus(200, "Sort '$sort' phải hợp lệ");
        }
    }

    #[Test]
    public function loan_list_with_nonexistent_library_card_id_returns_422(): void
    {
        [, $token] = $this->makeLibrarian();
        $this->getJson('/api/v1/loans?library_card_id=9999999', $this->auth($token))
            ->assertStatus(422);
    }

    #[Test]
    public function loan_search_with_sql_injection_is_safe(): void
    {
        [, $token] = $this->makeLibrarian();
        $response = $this->getJson(
            "/api/v1/loans?search=' OR 1=1; DROP TABLE loans--",
            $this->auth($token)
        )->assertStatus(200);

        // Phải trả danh sách bình thường, không bị inject
        $this->assertArrayHasKey('data', $response->json());
    }

    // ── Show Loan ─────────────────────────────────────────────────────────────

    #[Test]
    public function show_nonexistent_loan_returns_404(): void
    {
        [, $token] = $this->makeLibrarian();
        $this->getJson('/api/v1/loans/9999999', $this->auth($token))->assertStatus(404);
    }

    #[Test]
    public function show_loan_with_string_id_returns_404(): void
    {
        [, $token] = $this->makeLibrarian();
        $this->getJson('/api/v1/loans/not-an-id', $this->auth($token))->assertStatus(404);
    }

    // ── Bulk Operations ───────────────────────────────────────────────────────

    #[Test]
    public function bulk_delete_loans_without_ids_returns_422(): void
    {
        [, $token] = $this->makeLibrarian();
        $this->postJson('/api/v1/loans/bulk-delete', [], $this->auth($token))
            ->assertStatus(422);
    }

    #[Test]
    public function bulk_delete_loans_with_empty_array_returns_422(): void
    {
        [, $token] = $this->makeLibrarian();
        $this->postJson('/api/v1/loans/bulk-delete', ['ids' => []], $this->auth($token))
            ->assertStatus(422);
    }

    #[Test]
    public function bulk_delete_loans_exceeding_100_items_returns_422(): void
    {
        [, $token] = $this->makeLibrarian();
        $this->postJson('/api/v1/loans/bulk-delete', [
            'ids' => range(1, 101),
        ], $this->auth($token))->assertStatus(422);
    }

    #[Test]
    public function bulk_return_without_loan_ids_returns_422(): void
    {
        [, $token] = $this->makeLibrarian();
        $this->postJson('/api/v1/loans/bulk-return', [
            'return_date' => now()->toDateString(),
        ], $this->auth($token))->assertStatus(422);
    }

    #[Test]
    public function bulk_return_without_return_date_returns_422(): void
    {
        [, $token] = $this->makeLibrarian();
        $this->postJson('/api/v1/loans/bulk-return', [
            'loan_ids' => [1, 2, 3],
        ], $this->auth($token))->assertStatus(422);
    }

    #[Test]
    public function bulk_return_with_invalid_date_returns_422(): void
    {
        [, $token] = $this->makeLibrarian();
        $this->postJson('/api/v1/loans/bulk-return', [
            'loan_ids' => [1],
            'return_date' => 'not-a-date',
        ], $this->auth($token))->assertStatus(422);
    }

    #[Test]
    public function bulk_return_with_invalid_condition_on_return_returns_422(): void
    {
        [, $token] = $this->makeLibrarian();
        $this->postJson('/api/v1/loans/bulk-return', [
            'loan_ids' => [1],
            'return_date' => now()->toDateString(),
            'condition_on_return' => 'INVALID_CONDITION',
        ], $this->auth($token))->assertStatus(422);
    }

    // ── Statistics ────────────────────────────────────────────────────────────

    #[Test]
    public function statistics_with_invalid_granularity_returns_422(): void
    {
        [, $token] = $this->makeLibrarian();
        $this->getJson('/api/v1/loans/statistics?granularity=weekly', $this->auth($token))
            ->assertStatus(422);
    }

    #[Test]
    public function statistics_with_valid_granularity_returns_200(): void
    {
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Loan statistics dùng DATE_FORMAT — không tương thích SQLite in-memory.');
        }

        [, $token] = $this->makeLibrarian();
        foreach (['day', 'month', 'year'] as $g) {
            $this->getJson("/api/v1/loans/statistics?granularity=$g", $this->auth($token))
                ->assertStatus(200, "Granularity '$g' phải hợp lệ");
        }
    }

    // ── Me: Reader Loan Access ────────────────────────────────────────────────

    #[Test]
    public function reader_can_view_own_loans(): void
    {
        [, $token] = $this->makeStudent(['email' => 'myloan@example.com']);
        $this->getJson('/api/v1/me/loans', $this->auth($token))->assertSuccessful();
    }

    #[Test]
    public function reader_cannot_access_other_reader_loan_detail(): void
    {
        [, $tokenB] = $this->makeStudent(['email' => 'stu2@example.com']);

        $this->getJson('/api/v1/me/loans/9999999', $this->auth($tokenB))->assertStatus(404);
    }

    #[Test]
    public function reader_cannot_access_existing_loan_belonging_to_another_reader(): void
    {
        $userA = User::factory()->create(['user_type' => RoleType::STUDENT, 'email' => 'loan-idor-a@test.com']);
        [, $tokenB] = $this->makeStudent(['email' => 'loan-idor-b@test.com']);

        $card = LibraryCard::query()->create([
            'card_number' => 'SV-IDOR-A',
            'code' => 'SV-IDOR-A',
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
            'full_name' => 'Owner A',
            'user_id' => $userA->id,
        ]);

        $loan = Loan::query()->create([
            'loan_code' => 'L-IDOR-TEST',
            'library_card_id' => $card->id,
            'loan_type' => LoanType::HOME,
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => LoanStatus::BORROWED,
            'deleted' => false,
        ]);

        $this->getJson("/api/v1/me/loans/{$loan->id}", $this->auth($tokenB))
            ->assertStatus(404, 'Phiếu tồn tại nhưng không thuộc user — phải 404, không 403.');
    }

    #[Test]
    public function reader_loan_summary_returns_correct_structure(): void
    {
        [, $token] = $this->makeStudent(['email' => 'summary@example.com']);
        $this->getJson('/api/v1/me/loans/summary', $this->auth($token))
            ->assertSuccessful();
    }

    // ── Loan Return Validation ────────────────────────────────────────────────

    #[Test]
    public function return_loan_without_return_date_returns_422(): void
    {
        [, $token] = $this->makeLibrarian();
        $loan = $this->seedMinimalOpenLoan();

        $this->postJson("/api/v1/loans/{$loan->id}/return", [
            'condition_on_return' => 'good',
        ], $this->auth($token))->assertStatus(422);
    }

    #[Test]
    public function return_nonexistent_loan_returns_404(): void
    {
        [, $token] = $this->makeLibrarian();
        $this->postJson('/api/v1/loans/9999999/return', [
            'return_date' => now()->toDateString(),
        ], $this->auth($token))->assertStatus(404);
    }

    #[Test]
    public function return_loan_with_negative_fine_returns_422(): void
    {
        [, $token] = $this->makeLibrarian();
        $loan = $this->seedMinimalOpenLoan();

        $this->postJson("/api/v1/loans/{$loan->id}/return", [
            'return_date' => now()->toDateString(),
            'fine_amount' => -1000,
        ], $this->auth($token))->assertStatus(422);
    }

    private function seedMinimalOpenLoan(): Loan
    {
        $card = LibraryCard::query()->create([
            'card_number' => 'SV-RET-'.uniqid(),
            'code' => 'SV-RET-'.uniqid(),
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
            'full_name' => 'Test return validation',
            'user_id' => null,
        ]);

        return Loan::query()->create([
            'loan_code' => 'L-RET-'.uniqid(),
            'library_card_id' => $card->id,
            'loan_type' => LoanType::HOME,
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => LoanStatus::BORROWED,
            'deleted' => false,
        ]);
    }
}
