<?php

namespace Tests\Feature\Backend;

use App\Enums\LibraryCardStatus;
use App\Models\LibraryCard;
use Database\Seeders\LoanPoliciesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryCardLoanLookupTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LoanPoliciesSeeder::class);
    }

    public function test_lookup_for_loan_not_found_returns_404(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson(
            '/api/v1/library-cards/lookup-for-loan?card_number=KHONG-TON-TAI',
            $this->apiTokenHeaders($token)
        );

        $response->assertStatus(404)
            ->assertJsonPath('status', 'error');
    }

    public function test_lookup_for_loan_active_external_returns_onsite_only_and_limits(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        LibraryCard::query()->create([
            'card_number' => 'EXT-LOOKUP-001',
            'code' => 'EXT-LOOKUP-001-C',
            'holder_type' => LibraryCard::HOLDER_TYPE_EXTERNAL,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
        ]);

        $response = $this->getJson(
            '/api/v1/library-cards/lookup-for-loan?card_number='.rawurlencode('EXT-LOOKUP-001'),
            $this->apiTokenHeaders($token)
        );

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.allow_home', false)
            ->assertJsonPath('data.allow_onsite', true)
            ->assertJsonPath('data.limits.max_books', 5);
    }

    public function test_lookup_for_loan_pending_review_returns_422(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        LibraryCard::query()->create([
            'card_number' => 'PEND-LOOKUP-001',
            'code' => 'PEND-LOOKUP-001-C',
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_PENDING_REVIEW,
            'status' => LibraryCardStatus::PENDING,
        ]);

        $response = $this->getJson(
            '/api/v1/library-cards/lookup-for-loan?card_number='.rawurlencode('PEND-LOOKUP-001'),
            $this->apiTokenHeaders($token)
        );

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_lookup_for_loan_locked_card_returns_422_with_message(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        LibraryCard::query()->create([
            'card_number' => 'LOCK-LOOKUP-001',
            'code' => 'LOCK-LOOKUP-001-C',
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::LOCKED,
        ]);

        $response = $this->getJson(
            '/api/v1/library-cards/lookup-for-loan?card_number='.rawurlencode('LOCK-LOOKUP-001'),
            $this->apiTokenHeaders($token)
        );

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('messages', 'Thẻ đang bị khóa, không thể mượn sách.');
    }
}
