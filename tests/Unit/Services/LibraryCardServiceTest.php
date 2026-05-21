<?php

namespace Tests\Unit\Services;

use App\Enums\LibraryCardStatus;
use App\Enums\RoleType;
use App\Models\Faculty;
use App\Models\LibraryCard;
use App\Models\Period;
use App\Models\User;
use App\Services\LibraryCard\LibraryCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LibraryCardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_requires_class_faculty_and_period(): void
    {
        $faculty = Faculty::query()->create([
            'code' => 'CNTT',
            'name' => 'Công nghệ thông tin',
            'is_active' => true,
        ]);
        $period = Period::query()->create([
            'code' => 'K65',
            'name' => 'Khóa 65',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'user_type' => RoleType::STUDENT,
            'avatar' => 'avatars/student.jpg',
        ]);

        $this->expectException(ValidationException::class);
        app(LibraryCardService::class)->createForUserHaveAccount($user, [
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
        ]);
    }

    public function test_teacher_requires_faculty(): void
    {
        $user = User::factory()->create([
            'user_type' => RoleType::TEACHER,
            'avatar' => 'avatars/teacher.jpg',
        ]);

        $this->expectException(ValidationException::class);
        app(LibraryCardService::class)->createForUserHaveAccount($user, []);
    }

    public function test_student_creates_card_with_params(): void
    {
        $faculty = Faculty::query()->create([
            'code' => 'CK',
            'name' => 'Cơ khí',
            'is_active' => true,
        ]);
        $period = Period::query()->create([
            'code' => 'K66',
            'name' => 'Khóa 66',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'user_type' => RoleType::STUDENT,
            'avatar' => 'avatars/s.jpg',
        ]);

        $card = app(LibraryCardService::class)->createForUserHaveAccount($user, [
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'K66-CLC-01',
        ]);

        $this->assertSame($user->id, $card->user_id);
        $this->assertSame(LibraryCard::HOLDER_TYPE_STUDENT, $card->holder_type);
        $this->assertSame($faculty->id, $card->faculty_id);
        $this->assertSame($period->id, $card->period_id);
        $this->assertSame('K66-CLC-01', $card->class_code);
        $this->assertSame(LibraryCardStatus::PENDING, $card->status);
        $this->assertSame(LibraryCard::WORKFLOW_PENDING_REVIEW, $card->workflow_status);
        $this->assertSame((string) $user->code, $card->code);
        $this->assertSame((string) $user->code, $card->card_number);
        $this->assertSame(RoleType::STUDENT->value, $card->params['registration']['user_type']);
        $this->assertSame($faculty->id, $card->params['registration']['faculty_id']);
        $this->assertSame($period->id, $card->params['registration']['period_id']);
        $this->assertSame('K66-CLC-01', $card->params['registration']['class_code']);
    }

    public function test_account_paid_at_counter_sets_pending_pickup_and_records_payment(): void
    {
        $faculty = Faculty::query()->create([
            'code' => 'CK2',
            'name' => 'Cơ khí 2',
            'is_active' => true,
        ]);
        $period = Period::query()->create([
            'code' => 'K67',
            'name' => 'Khóa 67',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'user_type' => RoleType::STUDENT,
            'avatar' => 'avatars/counter.jpg',
        ]);

        $card = app(LibraryCardService::class)->createForUserHaveAccount($user, [
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'D21-02',
            'paid_at_counter' => true,
            'payment_amount' => 50_000,
            'payment_method' => 'cash',
            'receipt_number' => 'RCPT-001',
        ]);

        $this->assertSame(LibraryCard::WORKFLOW_PENDING_PICKUP, $card->workflow_status);
        $this->assertSame(LibraryCardStatus::PENDING, $card->status);
        $this->assertNotNull($card->payment);
        $this->assertSame(LibraryCard::PAYMENT_PAID, $card->payment->payment_status);
        $this->assertSame(50_000.0, (float) $card->payment->payment_amount);
        $this->assertSame('RCPT-001', $card->payment->receipt_number);
        $this->assertTrue($card->params['counter_registration']['paid_at_counter'] ?? false);
    }

    public function test_teacher_creates_card_with_faculty(): void
    {
        $faculty = Faculty::query()->create([
            'code' => 'VT',
            'name' => 'Vận tải',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'user_type' => RoleType::TEACHER,
            'avatar' => 'avatars/t.jpg',
        ]);

        $card = app(LibraryCardService::class)->createForUserHaveAccount($user, [
            'faculty_id' => $faculty->id,
        ]);

        $this->assertSame(LibraryCard::HOLDER_TYPE_TEACHER, $card->holder_type);
        $this->assertSame(LibraryCard::WORKFLOW_PENDING_REVIEW, $card->workflow_status);
        $this->assertSame($faculty->id, $card->faculty_id);
        $this->assertNull($card->period_id);
        $this->assertSame((string) $user->code, $card->card_number);
        $this->assertSame((string) $user->code, $card->code);
        $this->assertSame($faculty->id, $card->params['registration']['faculty_id']);
        $this->assertArrayNotHasKey('period_id', $card->params['registration']);
    }

    public function test_external_reader_with_account_no_faculty_required(): void
    {
        $user = User::factory()->create([
            'user_type' => RoleType::GUEST,
            'avatar' => 'avatars/g.jpg',
        ]);

        $card = app(LibraryCardService::class)->createForUserHaveAccount($user, []);

        $this->assertSame(LibraryCard::HOLDER_TYPE_EXTERNAL, $card->holder_type);
        $this->assertSame(LibraryCard::WORKFLOW_PENDING_REVIEW, $card->workflow_status);
        $this->assertNull($card->faculty_id);
        $this->assertNull($card->period_id);
    }

    public function test_librarian_cannot_use_account_registration_flow(): void
    {
        $user = User::factory()->create([
            'user_type' => RoleType::LIBRARIAN,
            'avatar' => 'a.jpg',
        ]);

        $this->expectException(ValidationException::class);
        app(LibraryCardService::class)->createForUserHaveAccount($user, []);
    }

    public function test_rejects_empty_holder_code(): void
    {
        $faculty = Faculty::query()->create([
            'code' => 'X',
            'name' => 'Khoa X',
            'is_active' => true,
        ]);
        $period = Period::query()->create([
            'code' => 'P1',
            'name' => 'Niên khóa 1',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'user_type' => RoleType::STUDENT,
            'avatar' => 'a.jpg',
            'code' => '',
        ]);

        $this->expectException(ValidationException::class);
        app(LibraryCardService::class)->createForUserHaveAccount($user, [
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'L01',
        ]);
    }

    public function test_uses_code_from_payload_over_user(): void
    {
        $faculty = Faculty::query()->create([
            'code' => 'Y',
            'name' => 'Khoa Y',
            'is_active' => true,
        ]);
        $period = Period::query()->create([
            'code' => 'P2',
            'name' => 'Niên khóa 2',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'user_type' => RoleType::STUDENT,
            'avatar' => 'b.jpg',
            'code' => '111',
        ]);

        $card = app(LibraryCardService::class)->createForUserHaveAccount($user, [
            'code' => '222',
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'L02',
        ]);

        $this->assertSame('222', $card->code);
        $this->assertSame('222', $card->card_number);
    }

    public function test_guest_without_account_external_active_and_paid_row(): void
    {
        $code = 'EXT-'.uniqid();
        $card = app(LibraryCardService::class)->create([
            'holder_type' => LibraryCard::HOLDER_TYPE_EXTERNAL,
            'code' => $code,
            'full_name' => 'Nguyễn Văn A',
            'email' => 'ext_'.uniqid().'@test.local',
            'phone' => '0909123456',
            'address' => 'Hà Nội',
            'date_of_birth' => '1990-01-15',
            'photo_path' => 'photos/a.jpg',
        ]);

        $this->assertNull($card->user_id);
        $this->assertSame(LibraryCard::WORKFLOW_ACTIVE, $card->workflow_status);
        $this->assertSame(LibraryCardStatus::ACTIVE, $card->status);
        $this->assertNotNull($card->payment);
        $this->assertSame(LibraryCard::PAYMENT_PAID, $card->payment->payment_status);
    }

    public function test_guest_without_account_student_pending_review(): void
    {
        $faculty = Faculty::query()->create([
            'code' => 'F9',
            'name' => 'K9',
            'is_active' => true,
        ]);
        $period = Period::query()->create([
            'code' => 'K9',
            'name' => 'NK9',
            'is_active' => true,
        ]);

        $code = 'ST-'.uniqid();
        $card = app(LibraryCardService::class)->create([
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'code' => $code,
            'full_name' => 'Sinh viên X',
            'email' => 'sv_'.uniqid().'@test.local',
            'phone' => '0909123000',
            'address' => 'UTC',
            'date_of_birth' => '2002-05-05',
            'photo_path' => 'photos/sv.jpg',
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'D21-01',
        ]);

        $this->assertSame(LibraryCard::WORKFLOW_PENDING_REVIEW, $card->workflow_status);
        $this->assertNull($card->payment);
    }

    public function test_staff_issued_with_linked_user_defaults_counter_paid_pending_pickup(): void
    {
        $faculty = Faculty::query()->create([
            'code' => 'FST',
            'name' => 'Khoa ST',
            'is_active' => true,
        ]);
        $period = Period::query()->create([
            'code' => 'PST',
            'name' => 'Năm ST',
            'is_active' => true,
        ]);
        $reader = User::factory()->create([
            'user_type' => RoleType::STUDENT,
            'code' => 'UT-'.uniqid(),
            'email' => 'reader_'.uniqid().'@test.local',
            'name' => 'Đọc giả quầy',
        ]);

        $card = app(LibraryCardService::class)->create([
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'user_id' => $reader->id,
            'code' => $reader->code,
            'full_name' => $reader->name,
            'email' => $reader->email,
            'phone' => '0909123456',
            'address' => 'Hà Nội',
            'date_of_birth' => '2000-01-01',
            'photo_path' => 'photos/staff.jpg',
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'D21-01',
        ]);

        $this->assertSame($reader->id, $card->user_id);
        $this->assertSame(LibraryCard::WORKFLOW_PENDING_PICKUP, $card->workflow_status);
        $this->assertSame(LibraryCardStatus::PENDING, $card->status);
        $this->assertNull($card->issue_date);
        $this->assertNull($card->expiry_date);
        $this->assertNotNull($card->payment);
        $this->assertSame(LibraryCard::PAYMENT_PAID, $card->payment->payment_status);
        $this->assertSame('staff_counter', data_get($card->params, 'registration.source'));
    }

    public function test_staff_issued_teacher_syncs_user_and_clears_student_fields_on_card(): void
    {
        $faculty = Faculty::query()->create([
            'code' => 'FGV',
            'name' => 'Khoa GV',
            'is_active' => true,
        ]);
        $period = Period::query()->create([
            'code' => 'PGV',
            'name' => 'Niên khóa cũ',
            'is_active' => true,
        ]);
        $reader = User::factory()->create([
            'user_type' => RoleType::STUDENT,
            'code' => 'GV-'.uniqid(),
            'email' => 'gv_'.uniqid().'@test.local',
            'name' => 'Sinh viên cũ',
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'D20-OLD',
        ]);

        $card = app(LibraryCardService::class)->create([
            'holder_type' => LibraryCard::HOLDER_TYPE_TEACHER,
            'user_id' => $reader->id,
            'code' => $reader->code,
            'full_name' => 'Nguyễn Văn GV',
            'email' => $reader->email,
            'phone' => '0909000111',
            'address' => 'TP.HCM',
            'date_of_birth' => '1985-03-10',
            'photo_path' => 'photos/gv.jpg',
            'faculty_id' => $faculty->id,
        ]);

        $reader->refresh();
        $this->assertSame(RoleType::TEACHER, $reader->user_type);
        $this->assertSame('Nguyễn Văn GV', $reader->name);
        $this->assertSame($faculty->id, $reader->faculty_id);
        $this->assertNull($reader->period_id);
        $this->assertNull($reader->class_code);
        $this->assertNull($card->period_id);
        $this->assertNull($card->class_code);
        $this->assertSame(LibraryCard::WORKFLOW_PENDING_PICKUP, $card->workflow_status);
    }

    public function test_staff_issued_second_card_for_same_user_raises_validation(): void
    {
        $faculty = Faculty::query()->create([
            'code' => 'F2',
            'name' => 'K2',
            'is_active' => true,
        ]);
        $period = Period::query()->create([
            'code' => 'P2',
            'name' => 'N2',
            'is_active' => true,
        ]);
        $reader = User::factory()->create([
            'user_type' => RoleType::STUDENT,
            'code' => 'UT2-'.uniqid(),
            'email' => 'reader2_'.uniqid().'@test.local',
            'name' => 'Đọc giả 2',
        ]);

        $base = [
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'user_id' => $reader->id,
            'code' => $reader->code,
            'full_name' => $reader->name,
            'email' => $reader->email,
            'phone' => '0909123456',
            'address' => 'Hà Nội',
            'date_of_birth' => '2000-01-01',
            'photo_path' => 'photos/staff.jpg',
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'D21-01',
        ];

        app(LibraryCardService::class)->create($base);

        $this->expectException(ValidationException::class);
        app(LibraryCardService::class)->create($base);
    }

    public function test_set_pending_payment_deadline_after_review(): void
    {
        $faculty = Faculty::query()->create(['code' => 'FA', 'name' => 'A', 'is_active' => true]);
        $period = Period::query()->create(['code' => 'PA', 'name' => 'P', 'is_active' => true]);
        $user = User::factory()->create(['user_type' => RoleType::STUDENT, 'avatar' => 'x.jpg']);

        $card = app(LibraryCardService::class)->createForUserHaveAccount($user, [
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'L1',
        ]);

        $updated = app(LibraryCardService::class)->setPendingPaymentDeadline($card->fresh());

        $this->assertSame(LibraryCard::WORKFLOW_PENDING_PAYMENT, $updated->workflow_status);
        $this->assertNotEmpty(data_get($updated->params, 'payment_due_at'));
        $this->assertNotEmpty(data_get($updated->params, 'payment_notice_sent_at'));
    }

    public function test_approve_pending_review_sets_pending_payment_and_records_reviewer(): void
    {
        Mail::fake();

        $faculty = Faculty::query()->create(['code' => 'FD', 'name' => 'D', 'is_active' => true]);
        $period = Period::query()->create(['code' => 'PD', 'name' => 'P4', 'is_active' => true]);
        $reader = User::factory()->create([
            'user_type' => RoleType::STUDENT,
            'avatar' => 'avatars/approve.jpg',
            'email' => 'svc@unit.test',
        ]);
        $librarian = User::factory()->create(['user_type' => RoleType::LIBRARIAN]);

        $card = app(LibraryCardService::class)->createForUserHaveAccount($reader, [
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'LX',
        ]);

        $updated = app(LibraryCardService::class)->approvePendingReviewAndActivate($card->fresh(), $librarian);

        Mail::assertNothingSent();

        $this->assertSame(LibraryCard::WORKFLOW_PENDING_PAYMENT, $updated->workflow_status);
        $this->assertSame(LibraryCardStatus::PENDING, $updated->status);
        $this->assertNull($updated->issue_date);
        $this->assertSame($librarian->id, $updated->reviewed_by);
        $this->assertNotNull($updated->reviewed_at);
    }

    public function test_normalize_legacy_workflow_status(): void
    {
        $this->assertSame(
            LibraryCard::WORKFLOW_PENDING_REVIEW,
            LibraryCard::normalizeWorkflowStatus(LibraryCard::WORKFLOW_DRAFT)
        );
        $this->assertSame(
            LibraryCard::WORKFLOW_ACTIVE,
            LibraryCard::normalizeWorkflowStatus(LibraryCard::WORKFLOW_REVOKED)
        );
        $this->assertSame(
            LibraryCard::WORKFLOW_PENDING_PICKUP,
            LibraryCard::normalizeWorkflowStatus('pending_pickup')
        );
    }

    public function test_confirm_pickup_activates_card(): void
    {
        $faculty = Faculty::query()->create(['code' => 'FCU', 'name' => 'CU', 'is_active' => true]);
        $period = Period::query()->create(['code' => 'PCU', 'name' => 'CU', 'is_active' => true]);
        $reader = User::factory()->create(['user_type' => RoleType::STUDENT, 'avatar' => 'x.jpg']);
        $staff = User::factory()->create(['user_type' => RoleType::LIBRARIAN]);

        $card = app(LibraryCardService::class)->createForUserHaveAccount($reader, [
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => 'CU1',
            'paid_at_counter' => true,
            'payment_amount' => 30_000,
            'payment_method' => 'cash',
        ]);

        $activated = app(LibraryCardService::class)->confirmPickupAndActivate($card->fresh(), $staff);

        $this->assertSame(LibraryCard::WORKFLOW_ACTIVE, $activated->workflow_status);
        $this->assertSame(LibraryCardStatus::ACTIVE, $activated->status);
        $this->assertNotNull($activated->issue_date);
        $this->assertNotNull($activated->expiry_date);
        $this->assertSame($staff->id, $activated->issued_by);
    }
}
