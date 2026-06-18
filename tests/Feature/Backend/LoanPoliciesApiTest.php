<?php

namespace Tests\Feature\Backend;

use App\Enums\BookStatus;
use App\Enums\LibraryCardStatus;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Classification;
use App\Models\LibraryCard;
use App\Models\LoanPolicy;
use App\Models\StorageCabinet;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\SeedsLoanPolicies;
use Tests\TestCase;

class LoanPoliciesApiTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;
    use SeedsLoanPolicies;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLoanPolicies();
    }

    #[Test]
    public function admin_can_list_loan_policies_with_params(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson('/api/v1/loan-policies', $this->apiTokenHeaders($token));

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $items = $response->json('data');
        if (isset($items['data']) && is_array($items['data'])) {
            $items = $items['data'];
        }

        $this->assertIsArray($items);
        $student = collect($items)->firstWhere('user_type', 'STUDENT');
        $this->assertNotNull($student);
        $this->assertSame(10, (int) ($student['params']['max_textbooks'] ?? 0));
        $this->assertSame(2, (int) ($student['params']['max_reference'] ?? 0));
    }

    #[Test]
    public function admin_can_update_loan_policy_params_max_textbooks_and_reference(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $policy = LoanPolicy::query()->where('user_type', 'TEACHER')->first();
        $this->assertNotNull($policy);

        $payload = [
            'code' => $policy->code,
            'name' => $policy->name,
            'user_type' => 'TEACHER',
            'max_books' => 10,
            'max_days' => 60,
            'max_renewals' => 2,
            'overdue_fine_per_day' => '5000.00',
            'allow_home' => true,
            'allow_onsite' => true,
            'params' => [
                'max_textbooks' => 8,
                'max_reference' => 4,
                'damage_fine_percent' => 0.1,
            ],
        ];

        $response = $this->putJson(
            "/api/v1/loan-policies/{$policy->id}",
            $payload,
            $this->apiTokenHeaders($token)
        );

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.params.max_textbooks', 8)
            ->assertJsonPath('data.params.max_reference', 4);

        $policy->refresh();
        $this->assertSame(8, $policy->params['max_textbooks'] ?? null);
        $this->assertSame(4, $policy->params['max_reference'] ?? null);
        $this->assertSame('5000.00', (string) $policy->overdue_fine_per_day);
    }

    #[Test]
    public function lookup_for_loan_uses_holder_type_policy_limits_from_database(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        LibraryCard::query()->create([
            'card_number' => 'SV-POLICY-LIMITS',
            'code' => 'SV-POLICY-LIMITS-C',
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
        ]);

        $response = $this->getJson(
            '/api/v1/library-cards/lookup-for-loan?card_number='.rawurlencode('SV-POLICY-LIMITS'),
            $this->apiTokenHeaders($token)
        );

        $response->assertOk()
            ->assertJsonPath('data.limits.max_books', 12)
            ->assertJsonPath('data.limits.max_textbooks', 10)
            ->assertJsonPath('data.limits.max_reference', 2)
            ->assertJsonPath('data.allow_home', true)
            ->assertJsonPath('data.allow_onsite', true);
    }

    #[Test]
    public function updating_policy_invalidates_cache_and_lookup_reflects_new_limits(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        LibraryCard::query()->create([
            'card_number' => 'SV-POLICY-UPDATE',
            'code' => 'SV-POLICY-UPDATE-C',
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
        ]);

        $policy = LoanPolicy::query()->where('user_type', 'STUDENT')->firstOrFail();

        $this->putJson("/api/v1/loan-policies/{$policy->id}", [
            'code' => $policy->code,
            'name' => $policy->name,
            'user_type' => 'STUDENT',
            'max_books' => 12,
            'max_days' => 30,
            'max_renewals' => 2,
            'overdue_fine_per_day' => '1000.00',
            'allow_home' => true,
            'allow_onsite' => true,
            'params' => [
                'max_textbooks' => 3,
                'max_reference' => 1,
                'damage_fine_percent' => 0.1,
            ],
        ], $this->apiTokenHeaders($token))->assertOk();

        $lookup = $this->getJson(
            '/api/v1/library-cards/lookup-for-loan?card_number='.rawurlencode('SV-POLICY-UPDATE'),
            $this->apiTokenHeaders($token)
        );

        $lookup->assertOk()
            ->assertJsonPath('data.limits.max_textbooks', 3)
            ->assertJsonPath('data.limits.max_reference', 1);
    }

    #[Test]
    public function home_loan_rejects_when_exceeding_max_textbooks_from_policy(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $card = LibraryCard::query()->create([
            'card_number' => 'SV-TEXTBOOK-CAP',
            'code' => 'SV-TEXTBOOK-CAP-C',
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
        ]);

        $policy = LoanPolicy::query()->where('user_type', 'STUDENT')->firstOrFail();
        $this->putJson("/api/v1/loan-policies/{$policy->id}", [
            'code' => $policy->code,
            'name' => $policy->name,
            'user_type' => 'STUDENT',
            'max_books' => 5,
            'max_days' => 30,
            'max_renewals' => 2,
            'overdue_fine_per_day' => '0',
            'allow_home' => true,
            'allow_onsite' => true,
            'params' => [
                'max_textbooks' => 1,
                'max_reference' => 5,
                'damage_fine_percent' => 0.1,
            ],
        ], $this->apiTokenHeaders($token))->assertOk();

        $bookOne = $this->seedTextbook('GT 1', 'BC-GT-001');
        $bookTwo = $this->seedTextbook('GT 2', 'BC-GT-002');

        $this->postJson('/api/v1/loans', [
            'library_card_id' => $card->id,
            'loan_type' => 'home',
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'da_muon',
            'book_ids' => [$bookOne->id],
            'quantity' => [1],
            'condition_on_loan' => ['tot'],
        ], $this->apiTokenHeaders($token))->assertCreated();

        $response = $this->postJson('/api/v1/loans', [
            'library_card_id' => $card->id,
            'loan_type' => 'home',
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'da_muon',
            'book_ids' => [$bookTwo->id],
            'quantity' => [1],
            'condition_on_loan' => ['tot'],
        ], $this->apiTokenHeaders($token));

        $response->assertStatus(422)
            ->assertJsonPath('messages', 'Vượt số lượng giáo trình tối đa theo chính sách mượn');
    }

    private function seedTextbook(string $title, string $barcode): Book
    {
        $warehouse = Warehouse::query()->create([
            'code' => 'KHO-'.uniqid(),
            'name' => 'Kho test',
            'is_active' => true,
        ]);
        $classification = Classification::query()->create([
            'code' => 'CL-'.uniqid(),
            'name' => 'Phan loai test',
        ]);
        StorageCabinet::query()->create([
            'warehouse_id' => $warehouse->id,
            'classification_id' => $classification->id,
            'code' => 'TU-'.uniqid(),
            'name' => 'Tu test',
            'current_quantity' => 0,
            'is_active' => true,
        ]);
        $book = Book::query()->create([
            'title' => $title,
            'resource_type' => 'textbook',
            'access_mode' => 'circulation_only',
            'quantity' => 2,
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
}
