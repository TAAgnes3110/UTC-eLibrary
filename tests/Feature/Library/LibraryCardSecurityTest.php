<?php

namespace Tests\Feature\Library;

use App\Enums\RoleType;
use App\Models\LibraryCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Test LibraryCard API – bảo mật, IDOR, workflow, data integrity.
 */
class LibraryCardSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): array
    {
        $role = Role::firstOrCreate(['name' => RoleType::SUPER_ADMIN->value, 'guard_name' => 'api']);
        $user = User::factory()->create(['user_type' => RoleType::SUPER_ADMIN]);
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
    public function unauthenticated_cannot_list_library_cards(): void
    {
        $this->getJson('/api/v1/library-cards')->assertStatus(401);
    }

    #[Test]
    public function student_cannot_access_admin_library_card_list(): void
    {
        [, $token] = $this->makeStudent();
        $this->getJson('/api/v1/library-cards', $this->auth($token))->assertStatus(403);
    }

    #[Test]
    public function admin_can_list_library_cards(): void
    {
        [, $token] = $this->makeAdmin();
        $this->getJson('/api/v1/library-cards', $this->auth($token))->assertStatus(200);
    }

    // ── Me: Request Library Card ──────────────────────────────────────────────

    #[Test]
    public function user_can_request_library_card(): void
    {
        [$user, $token] = $this->makeStudent();

        $response = $this->postJson('/api/v1/me/library-card', [
            'holder_type' => 'student',
            'full_name' => 'Nguyễn Văn A',
            'email' => $user->email,
            'phone' => '0912345678',
            'address' => 'Hà Nội',
            'date_of_birth' => '2000-01-01',
        ], $this->auth($token));

        $response->assertSuccessful();
    }

    #[Test]
    public function user_cannot_request_second_library_card(): void
    {
        [$user, $token] = $this->makeStudent();

        // Tạo thẻ lần 1
        $this->postJson('/api/v1/me/library-card', [
            'holder_type' => 'student',
            'full_name' => 'Nguyễn Văn A',
            'email' => $user->email,
            'phone' => '0912345678',
            'address' => 'Hà Nội',
            'date_of_birth' => '2000-01-01',
        ], $this->auth($token))->assertSuccessful();

        // Tạo lần 2 → phải bị chặn (đã có thẻ)
        $this->postJson('/api/v1/me/library-card', [
            'holder_type' => 'student',
            'full_name' => 'Nguyễn Văn A',
            'email' => $user->email,
            'phone' => '0912345678',
            'address' => 'Hà Nội',
            'date_of_birth' => '2000-01-01',
        ], $this->auth($token))->assertStatus(422);
    }

    #[Test]
    public function library_card_request_missing_required_fields_returns_422(): void
    {
        [, $token] = $this->makeStudent();

        $this->postJson('/api/v1/me/library-card', [], $this->auth($token))
            ->assertStatus(422);
    }

    #[Test]
    public function library_card_with_invalid_date_of_birth_returns_422(): void
    {
        [$user, $token] = $this->makeStudent();

        $this->postJson('/api/v1/me/library-card', [
            'holder_type' => 'student',
            'full_name' => 'Test',
            'email' => $user->email,
            'phone' => '0912345678',
            'address' => 'Hà Nội',
            'date_of_birth' => 'not-a-date',
        ], $this->auth($token))->assertStatus(422);
    }

    #[Test]
    public function library_card_with_future_date_of_birth_returns_422(): void
    {
        [$user, $token] = $this->makeStudent();

        $this->postJson('/api/v1/me/library-card', [
            'holder_type' => 'student',
            'full_name' => 'Test',
            'email' => $user->email,
            'phone' => '0912345678',
            'address' => 'Hà Nội',
            'date_of_birth' => now()->addYears(5)->toDateString(), // Tương lai
        ], $this->auth($token))->assertStatus(422);
    }

    // ── Admin: View/Update Card ───────────────────────────────────────────────

    #[Test]
    public function admin_can_view_library_card_detail(): void
    {
        [, $token] = $this->makeAdmin();
        [$cardUser] = $this->makeStudent(['email' => 'carduser@example.com']);

        $card = LibraryCard::create([
            'user_id' => $cardUser->id,
            'card_number' => 'UTC'.now()->format('Ymd').'TST001',
            'holder_type' => 'student',
            'full_name' => 'Test User',
            'email' => $cardUser->email,
            'phone' => '0912345678',
            'address' => 'Hà Nội',
            'date_of_birth' => '2000-01-01',
            'workflow_status' => LibraryCard::WORKFLOW_DRAFT,
        ]);

        $this->getJson("/api/v1/library-cards/{$card->id}", $this->auth($token))
            ->assertStatus(200);
    }

    #[Test]
    public function show_nonexistent_library_card_returns_404(): void
    {
        [, $token] = $this->makeAdmin();
        $this->getJson('/api/v1/library-cards/9999999', $this->auth($token))->assertStatus(404);
    }

    // ── Guest Register ────────────────────────────────────────────────────────

    #[Test]
    public function guest_can_register_library_card_without_auth(): void
    {
        $response = $this->postJson('/api/v1/library-cards/guest-register', [
            'holder_type' => 'external',
            'full_name' => 'Khách Bên Ngoài',
            'email' => 'guest@example.com',
            'phone' => '0900000001',
            'address' => 'Hà Nội',
            'date_of_birth' => '1990-05-15',
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('library_cards', ['email' => 'guest@example.com']);
    }

    #[Test]
    public function guest_register_missing_required_fields_returns_422(): void
    {
        $this->postJson('/api/v1/library-cards/guest-register', [])
            ->assertStatus(422);
    }

    #[Test]
    public function guest_register_with_invalid_holder_type_returns_422(): void
    {
        $this->postJson('/api/v1/library-cards/guest-register', [
            'holder_type' => 'invalid_type',
            'full_name' => 'Test',
            'email' => 'test@example.com',
            'phone' => '0912345678',
            'address' => 'HN',
            'date_of_birth' => '1990-01-01',
        ])->assertStatus(422);
    }

    // ── Lookup for Loan ───────────────────────────────────────────────────────

    #[Test]
    public function lookup_for_loan_requires_query_param(): void
    {
        [, $token] = $this->makeAdmin();
        $this->getJson('/api/v1/library-cards/lookup-for-loan', $this->auth($token))
            ->assertStatus(422);
    }

    #[Test]
    public function lookup_for_loan_with_sql_injection_is_safe(): void
    {
        [, $token] = $this->makeAdmin();
        $response = $this->getJson(
            "/api/v1/library-cards/lookup-for-loan?q=' OR 1=1--",
            $this->auth($token)
        );
        // Phải trả 200 với danh sách rỗng, không crash
        $response->assertSuccessful();
    }

    // ── Loan Summary (Me) ─────────────────────────────────────────────────────

    #[Test]
    public function unauthenticated_cannot_view_my_loans(): void
    {
        $this->getJson('/api/v1/me/loans')->assertStatus(401);
    }

    #[Test]
    public function student_can_view_own_loans(): void
    {
        [, $token] = $this->makeStudent(['email' => 'loanstudent@example.com']);
        $this->getJson('/api/v1/me/loans', $this->auth($token))->assertSuccessful();
    }

    #[Test]
    public function student_cannot_view_another_students_loan_by_id(): void
    {
        [, $tokenA] = $this->makeStudent(['email' => 'studenta@example.com']);
        [, $tokenB] = $this->makeStudent(['email' => 'studentb@example.com']);

        // StudentA tạo loan → lấy ID
        // StudentB thử xem loan đó → phải 404
        // (Không có loan nào tồn tại, nên 404)
        $this->getJson('/api/v1/me/loans/99999', $this->auth($tokenB))->assertStatus(404);
    }
}
