<?php

namespace Tests\Feature\Backend;

use App\Enums\LibraryCardStatus;
use App\Models\LibraryCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryCardApiTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    public function test_me_library_card_returns_null_when_user_has_no_card(): void
    {
        [, $token] = $this->createUserAndToken();

        $response = $this->getJson('/api/v1/me/library-card', $this->apiTokenHeaders($token));

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.library_card', null);
    }

    public function test_me_library_card_returns_card_payload(): void
    {
        [$user, $token] = $this->createUserAndToken();

        $card = LibraryCard::query()->create([
            'user_id' => $user->id,
            'card_number' => 'UTC-TEST-'.uniqid(),
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
            'is_active' => true,
            'full_name' => $user->name,
            'code' => $user->code,
        ]);
        $card->payment()->create([
            'payment_status' => LibraryCard::PAYMENT_PAID,
        ]);

        $response = $this->getJson('/api/v1/me/library-card', $this->apiTokenHeaders($token));

        $response->assertStatus(200)
            ->assertJsonPath('data.library_card.code', $user->code);
    }

    public function test_library_cards_index_returns_200_for_admin(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->getJson('/api/v1/library-cards', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonStructure(['status', 'data']);
    }
}
