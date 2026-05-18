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
use Tests\TestCase;

/**
 * Me loans — IDOR thật, enumeration export, validation đầu vào.
 */
class MeLoanIdorAndInputTest extends TestCase
{
    use RefreshDatabase;

    private function auth(string $token): array
    {
        return ['Authorization' => "Bearer $token", 'Accept' => 'application/json'];
    }

    private function studentWithCard(string $email): array
    {
        $user = User::factory()->create(['user_type' => RoleType::STUDENT, 'email' => $email]);
        $card = LibraryCard::query()->create([
            'card_number' => 'SV-'.substr(md5($email), 0, 8),
            'code' => 'SV-'.substr(md5($email), 0, 8),
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
            'full_name' => 'Độc giả '.$email,
            'user_id' => $user->id,
        ]);

        return [$user, $card, JWTAuth::fromUser($user)];
    }

    private function loanForCard(LibraryCard $card): Loan
    {
        return Loan::query()->create([
            'loan_code' => 'L-'.uniqid(),
            'library_card_id' => $card->id,
            'loan_type' => LoanType::HOME,
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => LoanStatus::BORROWED,
            'deleted' => false,
        ]);
    }

    #[Test]
    public function reader_getting_another_readers_existing_loan_returns_404_not_403(): void
    {
        [, $cardA] = $this->studentWithCard('loan-owner-a@test.com');
        [, , $tokenB] = $this->studentWithCard('loan-owner-b@test.com');

        $loanA = $this->loanForCard($cardA);

        $this->getJson("/api/v1/me/loans/{$loanA->id}", $this->auth($tokenB))
            ->assertStatus(404, 'Không được trả 403 — lộ sự tồn tại của phiếu mượn người khác.');
    }

    #[Test]
    public function reader_cannot_delete_another_readers_returned_loan(): void
    {
        [, $cardA] = $this->studentWithCard('loan-del-a@test.com');
        [, , $tokenB] = $this->studentWithCard('loan-del-b@test.com');

        $loanA = Loan::query()->create([
            'loan_code' => 'L-DEL-'.uniqid(),
            'library_card_id' => $cardA->id,
            'loan_type' => LoanType::HOME,
            'loan_date' => now()->subDays(14)->toDateString(),
            'due_date' => now()->subDays(7)->toDateString(),
            'return_date' => now()->subDays(5)->toDateString(),
            'status' => LoanStatus::RETURNED,
            'deleted' => false,
        ]);

        $this->deleteJson("/api/v1/me/loans/{$loanA->id}", [], $this->auth($tokenB))
            ->assertStatus(404);

        $this->assertFalse((bool) $loanA->fresh()->deleted);
    }

    #[Test]
    public function export_with_nonexistent_loan_id_in_ids_returns_empty_export_without_422(): void
    {
        [, , $token] = $this->studentWithCard('export-enum@test.com');

        // Không dùng exists:loans,id — tránh enumeration ID phiếu toàn hệ thống; scope user → file rỗng.
        $this->getJson('/api/v1/me/loans/export?'.http_build_query(['ids' => [9999999]]), $this->auth($token))
            ->assertSuccessful();
    }

    #[Test]
    public function export_with_other_users_loan_id_does_not_leak_loan_data(): void
    {
        [, $cardA] = $this->studentWithCard('export-a@test.com');
        [, , $tokenB] = $this->studentWithCard('export-b@test.com');

        $loanA = $this->loanForCard($cardA);

        $response = $this->getJson(
            '/api/v1/me/loans/export?'.http_build_query(['ids' => [$loanA->id]]),
            $this->auth($tokenB)
        );

        $response->assertSuccessful();
        $this->assertStringNotContainsString($loanA->loan_code, $response->getContent());
    }

    #[Test]
    public function me_loans_list_with_invalid_status_does_not_error_500(): void
    {
        [, , $token] = $this->studentWithCard('status-filter@test.com');

        $this->getJson('/api/v1/me/loans?status=INVALID_STATUS_XYZ', $this->auth($token))
            ->assertSuccessful();
    }

    #[Test]
    public function me_loans_list_with_per_page_over_100_returns_422(): void
    {
        [, , $token] = $this->studentWithCard('perpage@test.com');

        $this->getJson('/api/v1/me/loans?per_page=200', $this->auth($token))
            ->assertStatus(422);
    }

    #[Test]
    public function renewal_request_on_other_users_loan_is_rejected(): void
    {
        [, $cardA] = $this->studentWithCard('renew-a@test.com');
        [, , $tokenB] = $this->studentWithCard('renew-b@test.com');

        $loanA = $this->loanForCard($cardA);

        $response = $this->postJson(
            "/api/v1/me/loans/{$loanA->id}/renewal-requests",
            ['request_note' => 'Gia hạn'],
            $this->auth($tokenB)
        );

        $this->assertContains($response->status(), [403, 404, 422]);
    }
}
