<?php

namespace Tests\Feature\Modules;

use App\Enums\LibraryCardStatus;
use App\Enums\LoanStatus;
use App\Enums\LoanType;
use App\Enums\RoleType;
use App\Models\LibraryCard;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ModuleTestHelpers;
use Tests\TestCase;

/**
 * Module: Độc giả — phiếu mượn /me/loans (10 case).
 *
 * Case 2,3: mong đợi 404 khi IDOR (hiện app có thể 403 — test ghi nhận lệch).
 */
class MeLoanModuleTest extends TestCase
{
    use ModuleTestHelpers;
    use RefreshDatabase;

    private function cardFor(User $user): LibraryCard
    {
        return LibraryCard::query()->create([
            'card_number' => 'SV-'.uniqid(),
            'code' => 'SV-'.uniqid(),
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
            'full_name' => $user->name,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function case01_unauthenticated_loans_list_returns_401(): void
    {
        $this->getJson('/api/v1/me/loans')->assertStatus(401);
    }

    #[Test]
    public function case02_own_loans_list_returns_200(): void
    {
        [, $h] = $this->studentContext();
        $this->getJson('/api/v1/me/loans', $h)->assertSuccessful();
    }

    #[Test]
    public function case03_other_users_existing_loan_returns_404(): void
    {
        $owner = User::factory()->create(['user_type' => RoleType::STUDENT]);
        $card = $this->cardFor($owner);
        $loan = Loan::query()->create([
            'loan_code' => 'L-'.uniqid(),
            'library_card_id' => $card->id,
            'loan_type' => LoanType::HOME,
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => LoanStatus::BORROWED,
            'deleted' => false,
        ]);
        [, $h] = $this->studentContext();
        $this->getJson("/api/v1/me/loans/{$loan->id}", $h)->assertStatus(404);
    }

    #[Test]
    public function case04_nonexistent_loan_returns_404(): void
    {
        [, $h] = $this->studentContext();
        $this->getJson('/api/v1/me/loans/9999999', $h)->assertStatus(404);
    }

    #[Test]
    public function case05_loans_summary_returns_200(): void
    {
        [, $h] = $this->studentContext();
        $this->getJson('/api/v1/me/loans/summary', $h)->assertSuccessful();
    }

    #[Test]
    public function case06_per_page_over_100_returns_422(): void
    {
        [, $h] = $this->studentContext();
        $this->getJson('/api/v1/me/loans?per_page=200', $h)->assertStatus(422);
    }

    #[Test]
    public function case07_export_unknown_loan_id_returns_empty_export(): void
    {
        [, $h] = $this->studentContext();
        $this->getJson('/api/v1/me/loans/export?ids[]=9999999', $h)->assertSuccessful();
    }

    #[Test]
    public function case08_export_other_user_loan_does_not_contain_loan_code(): void
    {
        $owner = User::factory()->create(['user_type' => RoleType::STUDENT]);
        $loan = Loan::query()->create([
            'loan_code' => 'SECRET-CODE-XYZ',
            'library_card_id' => $this->cardFor($owner)->id,
            'loan_type' => LoanType::HOME,
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => LoanStatus::BORROWED,
            'deleted' => false,
        ]);
        [, $h] = $this->studentContext();
        $r = $this->getJson("/api/v1/me/loans/export?ids[]={$loan->id}", $h);
        $r->assertSuccessful();
        $this->assertStringNotContainsString('SECRET-CODE-XYZ', $r->getContent());
    }

    #[Test]
    public function case09_invalid_sort_still_returns_200_or_422(): void
    {
        [, $h] = $this->studentContext();
        $r = $this->getJson('/api/v1/me/loans?sort=INVALID', $h);
        $this->assertContains($r->status(), [200, 422]);
    }

    #[Test]
    public function case10_renewal_on_nonexistent_loan_returns_404(): void
    {
        [, $h] = $this->studentContext();
        $this->postJson('/api/v1/me/loans/9999999/renewal-requests', [], $h)
            ->assertStatus(404);
    }
}
