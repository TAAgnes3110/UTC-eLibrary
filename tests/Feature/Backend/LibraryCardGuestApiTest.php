<?php

namespace Tests\Feature\Backend;

use App\Enums\LibraryCardStatus;
use App\Models\LibraryCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryCardGuestApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_register_validation_returns_api_error_envelope(): void
    {
        $response = $this->postJson('/api/v1/library-cards/guest-register', []);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonStructure(['errors']);
    }

    public function test_guest_register_fails_when_rejected_application_exists_for_same_identity(): void
    {
        $email = 'rejected-guest-'.uniqid().'@example.com';
        $code = 'REJ-'.uniqid();

        LibraryCard::query()->create([
            'card_number' => $code,
            'code' => $code,
            'holder_type' => LibraryCard::HOLDER_TYPE_EXTERNAL,
            'full_name' => 'A',
            'email' => $email,
            'phone' => '0909000111',
            'address' => 'HN',
            'date_of_birth' => '1990-01-01',
            'photo_path' => 'p.jpg',
            'workflow_status' => LibraryCard::WORKFLOW_REJECTED,
            'status' => LibraryCardStatus::LOCKED,
        ]);

        $response = $this->postJson('/api/v1/library-cards/guest-register', [
            'holder_type' => LibraryCard::HOLDER_TYPE_EXTERNAL,
            'code' => $code,
            'full_name' => 'B',
            'email' => $email,
            'phone' => '0909000222',
            'address' => 'Hà Nội khác',
            'date_of_birth' => '1991-02-02',
            'photo_path' => 'p2.jpg',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
        $errors = $response->json('errors') ?? [];
        $this->assertTrue(
            isset($errors['email']) || isset($errors['code']) || isset($errors['phone']) || isset($errors['address']),
            'Expected validation on identity fields'
        );
    }

    public function test_guest_register_succeeds_when_rejected_row_was_soft_deleted(): void
    {
        $email = 'after-reject-'.uniqid().'@example.com';
        $code = 'AFTER-REJ-'.uniqid();

        $card = LibraryCard::query()->create([
            'card_number' => $code,
            'code' => $code,
            'holder_type' => LibraryCard::HOLDER_TYPE_EXTERNAL,
            'full_name' => 'A',
            'email' => $email,
            'phone' => '0909000333',
            'address' => 'HN',
            'date_of_birth' => '1990-01-01',
            'photo_path' => 'p.jpg',
            'workflow_status' => LibraryCard::WORKFLOW_REJECTED,
            'status' => LibraryCardStatus::LOCKED,
        ]);
        $card->delete();

        $response = $this->postJson('/api/v1/library-cards/guest-register', [
            'holder_type' => LibraryCard::HOLDER_TYPE_EXTERNAL,
            'code' => $code,
            'full_name' => 'B',
            'email' => $email,
            'phone' => '0909000444',
            'address' => 'Hà Nội khác',
            'date_of_birth' => '1991-02-02',
            'photo_path' => 'p2.jpg',
        ]);

        $response->assertStatus(201)->assertJsonPath('status', 'success');
    }
}
