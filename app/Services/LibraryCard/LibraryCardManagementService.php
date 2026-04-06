<?php

namespace App\Services\LibraryCard;

use App\Enums\LibraryCardStatus;
use App\Enums\RoleType;
use App\Helpers\FileHelpers;
use App\Helpers\Helpers;
use App\Helpers\StudentTeacherRegistrationHelper;
use App\Models\LibraryCard;
use App\Models\LibraryCardPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LibraryCardManagementService
{
    public const PER_PAGE = 50;

    public const PAYMENT_DUE_DAYS = 3;

    /** @var list<string> */
    private const KEYWORD_SEARCH_COLUMNS = ['card_number', 'code', 'full_name', 'email', 'phone'];

    /**
     * Danh sách thẻ (admin) — lọc theo từ khóa (cột tùy chọn), workflow, loại bạn đọc, trạng thái thẻ.
     *
     * @param  list<string>|null  $workflowStatuses
     * @param  list<string>|null  $keywordColumns  Cột áp dụng từ khóa; null = mọi cột trong {@see self::KEYWORD_SEARCH_COLUMNS}
     */
    public function index(
        ?string $keyword,
        int $perPage = self::PER_PAGE,
        ?array $workflowStatuses = null,
        ?string $holderType = null,
        ?int $cardStatus = null,
        ?array $keywordColumns = null,
        bool $managementListOnly = false,
    ): LengthAwarePaginator {
        $query = LibraryCard::query()
            ->with([
                'payment.collector:id,name',
                'period',
                'faculty',
                'department',
                'user:id,name,email,code',
            ])
            ->orderByDesc('id');

        if ($managementListOnly) {
            $query->whereIn('workflow_status', [
                LibraryCard::WORKFLOW_ACTIVE,
                LibraryCard::WORKFLOW_PENDING_PICKUP,
            ]);
        }

        if ($workflowStatuses !== null && $workflowStatuses !== []) {
            $query->whereIn('workflow_status', $workflowStatuses);
        }

        if ($holderType !== null && $holderType !== '') {
            $query->where('holder_type', $holderType);
        }

        if ($cardStatus !== null) {
            $query->where('status', $cardStatus);
        }

        if ($keyword !== null && $keyword !== '') {
            $kw = trim($keyword);
            $cols = $keywordColumns;
            if ($cols === null || $cols === []) {
                $cols = self::KEYWORD_SEARCH_COLUMNS;
            } else {
                $cols = array_values(array_intersect($cols, self::KEYWORD_SEARCH_COLUMNS));
            }
            if ($cols === []) {
                $cols = self::KEYWORD_SEARCH_COLUMNS;
            }
            $query->where(function ($q) use ($kw, $cols) {
                foreach ($cols as $col) {
                    $q->orWhere($col, 'like', "%{$kw}%");
                }
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Cập nhật ảnh thẻ (upload file).
     */
    public function updatePhoto(LibraryCard $card, UploadedFile $file): LibraryCard
    {
        FileHelpers::updateModelImage(
            $card,
            $file,
            'library_cards',
            'photo_path',
            $card->code ?: (string) $card->id
        );

        return $card->fresh();
    }

    /**
     * Xóa mềm (đưa vào thùng rác — {@see LibraryCard} dùng SoftDeletes).
     */
    public function destroy(LibraryCard $card): void
    {
        DB::transaction(fn () => $card->delete());
    }

    /**
     * Danh sách thẻ đã xóa mềm.
     */
    public function trash(int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        return LibraryCard::onlyTrashed()
            ->with([
                'payment.collector:id,name',
                'period',
                'faculty',
                'department',
                'user:id,name,email,code',
            ])
            ->orderByDesc('deleted_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function restore(int $id): ?LibraryCard
    {
        $card = LibraryCard::onlyTrashed()->find($id);
        if ($card === null) {
            return null;
        }
        $card->restore();

        return $card->fresh();
    }

    /** @return int số bản ghi đã khôi phục */
    public function restoreMany(array $ids): int
    {
        $ids = array_values(array_filter($ids, static fn ($v) => is_numeric($v)));
        if ($ids === []) {
            return 0;
        }

        return (int) LibraryCard::onlyTrashed()->whereIn('id', $ids)->restore();
    }

    /**
     * Xóa vĩnh viễn một thẻ đang ở thùng rác.
     */
    public function forceDeleteTrashed(int $id): bool
    {
        $card = LibraryCard::onlyTrashed()->find($id);
        if ($card === null) {
            return false;
        }
        $card->forceDelete();

        return true;
    }

    /** @return int số bản ghi đã xóa vĩnh viễn */
    public function forceDeleteManyTrashed(array $ids): int
    {
        $ids = array_values(array_filter($ids, static fn ($v) => is_numeric($v)));
        if ($ids === []) {
            return 0;
        }

        return (int) LibraryCard::onlyTrashed()->whereIn('id', $ids)->forceDelete();
    }

    private const UPDATABLE_ATTRIBUTES = [
        'full_name',
        'email',
        'phone',
        'address',
        'date_of_birth',
        'photo_path',
        'external_organization',
        'faculty_id',
        'department_id',
        'period_id',
        'class_code',
        'holder_type',
        'card_number',
        'notes',
        'workflow_status',
        'issue_date',
        'expiry_date',
        'revoked_at',
        'revoked_reason',
        'status',
    ];

    /**
     * @param  array<string, mixed>  $data
     * @return array{holder_type: string, faculty_id: int, period_id: int, class_code: string}
     */
    public function studentAffiliationPayload(array $data): array
    {
        $aff = StudentTeacherRegistrationHelper::assertAndExtractStudentAffiliation($data);

        return [
            'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
            'faculty_id' => $aff['faculty_id'],
            'period_id' => $aff['period_id'],
            'class_code' => $aff['class_code'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{holder_type: string, faculty_id: int}
     */
    public function teacherAffiliationPayload(array $data): array
    {
        return [
            'holder_type' => LibraryCard::HOLDER_TYPE_TEACHER,
            'faculty_id' => StudentTeacherRegistrationHelper::assertAndExtractTeacherFacultyId($data),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function attachRegistrationMetadata(
        array $payload,
        array $data,
        string $source,
        string $actorLabel,
        bool $includeSchoolFields
    ): array {
        $params = is_array($data['params'] ?? null) ? $data['params'] : [];

        $registration = [
            'source' => $source,
            'user_type' => $actorLabel,
        ];

        if ($includeSchoolFields) {
            if (isset($payload['faculty_id'])) {
                $registration['faculty_id'] = $payload['faculty_id'];
            }
            if (isset($payload['period_id'])) {
                $registration['period_id'] = $payload['period_id'];
            }
            if (isset($payload['class_code'])) {
                $registration['class_code'] = $payload['class_code'];
            }
        }

        if (isset($payload['department_id'])) {
            $registration['department_id'] = $payload['department_id'];
        }

        $params['registration'] = array_merge($params['registration'] ?? [], $registration);

        return $params;
    }

    public function setPendingPaymentDeadline(LibraryCard $card): LibraryCard
    {
        return $this->markPendingPaymentAfterPaymentNotice($card, Carbon::now(), null);
    }

    /**
     * Duyệt hồ sơ chờ xác nhận → thẻ hoạt động (không qua thanh toán trực tuyến).
     */
    public function approvePendingReviewAndActivate(LibraryCard $card, ?User $reviewer): LibraryCard
    {
        return DB::transaction(function () use ($card, $reviewer) {
            $card = $card->fresh();
            if ($card->workflow_status !== LibraryCard::WORKFLOW_PENDING_REVIEW) {
                throw ValidationException::withMessages([
                    'workflow_status' => [__('Chỉ duyệt được hồ sơ đang chờ xác nhận.')],
                ]);
            }

            $today = Carbon::today();
            $params = $card->params ?? [];
            unset($params['payment_notice_sent_at'], $params['payment_due_at']);

            $card->workflow_status = LibraryCard::WORKFLOW_ACTIVE;
            $card->status = LibraryCardStatus::ACTIVE;
            $card->issue_date = $today;
            $card->expiry_date = $today->copy()->addYear();
            $card->params = $params;

            if ($reviewer !== null) {
                $card->reviewed_by = $reviewer->id;
                $card->reviewed_at = now();
            }

            $card->save();

            return $card->fresh();
        });
    }

    /**
     * Từ chối hồ sơ chờ xác nhận → workflow rejected rồi xóa mềm (không còn trên danh sách hoạt động).
     */
    public function rejectPendingReview(LibraryCard $card, ?string $notes, ?User $reviewer): LibraryCard
    {
        return DB::transaction(function () use ($card, $notes, $reviewer) {
            $card = $card->fresh();
            if ($card->workflow_status !== LibraryCard::WORKFLOW_PENDING_REVIEW) {
                throw ValidationException::withMessages([
                    'workflow_status' => [__('Chỉ từ chối được hồ sơ đang chờ xác nhận.')],
                ]);
            }
            if (Helpers::filled($notes)) {
                $card->notes = $notes;
            }
            $card->workflow_status = LibraryCard::WORKFLOW_REJECTED;
            if ($reviewer !== null) {
                $card->reviewed_by = $reviewer->id;
                $card->reviewed_at = now();
            }
            $card->save();

            return $this->returnAfterRejectedOrCancelledTrash($card);
        });
    }

    /**
     * @param  Carbon  $noticeSentAt  Mốc “đã gửi thông báo thanh toán” — hạn = mốc này + {@see PAYMENT_DUE_DAYS}.
     */
    private function markPendingPaymentAfterPaymentNotice(LibraryCard $card, Carbon $noticeSentAt, ?User $reviewer): LibraryCard
    {
        return DB::transaction(function () use ($card, $noticeSentAt, $reviewer) {
            $card = $card->fresh();
            $params = $card->params ?? [];
            $params['payment_notice_sent_at'] = $noticeSentAt->toIso8601String();
            $params['payment_due_at'] = $noticeSentAt->copy()->addDays(self::PAYMENT_DUE_DAYS)->toIso8601String();

            $card->workflow_status = LibraryCard::WORKFLOW_PENDING_PAYMENT;
            $card->params = $params;

            if ($reviewer !== null) {
                $card->reviewed_by = $reviewer->id;
                $card->reviewed_at = $noticeSentAt;
            }

            $card->save();

            return $card->fresh();
        });
    }

    /**
     * Cập nhật thông tin thẻ.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateLibraryCard(LibraryCard $card, array $data): LibraryCard
    {
        return DB::transaction(function () use ($card, $data) {
            $card = $card->fresh();
            $allowed = array_flip(self::UPDATABLE_ATTRIBUTES);

            foreach (array_intersect_key($data, $allowed) as $key => $value) {
                if ($key === 'status') {
                    if ($value === null) {
                        continue;
                    }
                    $card->status = $value instanceof LibraryCardStatus
                        ? $value
                        : LibraryCardStatus::from((int) $value);

                    continue;
                }
                $card->{$key} = $value;
            }

            if (isset($data['params']) && is_array($data['params'])) {
                $card->params = array_replace_recursive($card->params ?? [], $data['params']);
            }

            $ht = $card->holder_type;
            $ht = $ht instanceof \BackedEnum ? $ht->value : (string) $ht;
            if ($ht === LibraryCard::HOLDER_TYPE_EXTERNAL) {
                $card->faculty_id = null;
                $card->department_id = null;
                $card->period_id = null;
                $card->class_code = null;
            }
            if (in_array($ht, [LibraryCard::HOLDER_TYPE_STUDENT, LibraryCard::HOLDER_TYPE_TEACHER], true)) {
                $card->external_organization = null;
            }

            $card->save();

            return $this->returnAfterRejectedOrCancelledTrash($card);
        });
    }

    /**
     * Từ chối / hủy → không còn thẻ trong danh sách hoạt động: xóa mềm sau khi ghi workflow.
     */
    private function returnAfterRejectedOrCancelledTrash(LibraryCard $card): LibraryCard
    {
        $card = $card->fresh();
        $ws = $card->workflow_status;
        $ws = $ws instanceof \BackedEnum ? $ws->value : (string) $ws;
        if (in_array($ws, [LibraryCard::WORKFLOW_REJECTED, LibraryCard::WORKFLOW_CANCELLED], true)) {
            $card->delete();
        }

        return LibraryCard::withTrashed()->findOrFail($card->id);
    }

    /**
     * Hủy hồ sơ nghiệp vụ — workflow cancelled rồi xóa mềm (thùng rác admin).
     */
    public function cancelLibraryCardApplication(LibraryCard $card, ?string $reason = null): LibraryCard
    {
        return DB::transaction(function () use ($card, $reason) {
            $card = $card->fresh();
            $params = $card->params ?? [];
            if (Helpers::filled($reason)) {
                $params['cancel_reason'] = $reason;
            }
            $card->params = $params;
            $card->workflow_status = LibraryCard::WORKFLOW_CANCELLED;
            $card->save();
            $card->delete();

            return LibraryCard::withTrashed()->findOrFail($card->id);
        });
    }

    /**
     * Xóa vĩnh viễn một bản ghi thẻ (kể cả chưa qua thùng rác) — payment cascade theo FK.
     */
    public function permanentlyDeleteLibraryCard(LibraryCard $card): void
    {
        DB::transaction(fn () => $card->fresh()->forceDelete());
    }

    public function setWorkflowStatus(LibraryCard $card, string $workflowStatus): LibraryCard
    {
        $allowed = [
            LibraryCard::WORKFLOW_DRAFT,
            LibraryCard::WORKFLOW_PENDING_PAYMENT,
            LibraryCard::WORKFLOW_PENDING_REVIEW,
            LibraryCard::WORKFLOW_PENDING_PICKUP,
            LibraryCard::WORKFLOW_ACTIVE,
            LibraryCard::WORKFLOW_REJECTED,
            LibraryCard::WORKFLOW_CANCELLED,
            LibraryCard::WORKFLOW_EXPIRED,
            LibraryCard::WORKFLOW_REVOKED,
        ];
        if (! in_array($workflowStatus, $allowed, true)) {
            throw ValidationException::withMessages([
                'workflow_status' => [__('Trạng thái quy trình không hợp lệ.')],
            ]);
        }
        $card->workflow_status = $workflowStatus;
        $card->save();

        return $this->returnAfterRejectedOrCancelledTrash($card);
    }

    public function setCardStatus(LibraryCard $card, LibraryCardStatus $status): LibraryCard
    {
        $card->status = $status;
        $card->save();

        return $card->fresh();
    }

    /**
     * Thủ thư gắn thẻ với tài khoản bạn đọc.
     */
    public function assertUserEligibleForStaffIssuedCard(User $user): void
    {
        $role = $user->user_type;
        if (! $role instanceof RoleType) {
            throw ValidationException::withMessages([
                'user_id' => [__('Không xác định loại tài khoản.')],
            ]);
        }
        if (in_array($role, [RoleType::SUPER_ADMIN, RoleType::ADMIN, RoleType::LIBRARIAN], true)) {
            throw ValidationException::withMessages([
                'user_id' => [__('Không gắn thẻ bạn đọc vào tài khoản nội bộ.')],
            ]);
        }
        if (LibraryCard::query()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'user_id' => [__('Người dùng đã có hồ sơ thẻ trong hệ thống.')],
            ]);
        }
    }

    /**
     * Ghi nhận thanh toán lệ phí tại quầy
     *
     * @param  array<string, mixed>  $data
     */
    public function recordWalkInPayment(LibraryCard $card, array $data): void
    {
        LibraryCardPayment::query()->create([
            'library_card_id' => $card->id,
            'payment_status' => LibraryCard::PAYMENT_PAID,
            'paid_at' => now(),
            'payment_amount' => isset($data['payment_amount']) ? (float) $data['payment_amount'] : 0,
            'payment_method' => isset($data['payment_method']) ? (string) $data['payment_method'] : 'walk_in',
            'receipt_number' => isset($data['receipt_number']) ? (string) $data['receipt_number'] : null,
        ]);
    }

    /**
     * Thẻ đăng ký khách (user_id null) trùng mã định danh với user mới → gắn thẻ vào tài khoản.
     */
    public function linkOrphanGuestCardToNewUser(User $user): ?LibraryCard
    {
        $code = trim((string) ($user->code ?? ''));
        if ($code === '') {
            return null;
        }

        return DB::transaction(function () use ($user, $code) {
            if (LibraryCard::query()->where('user_id', $user->id)->lockForUpdate()->exists()) {
                return null;
            }

            $card = LibraryCard::query()
                ->whereNull('user_id')
                ->where(function ($q) use ($code) {
                    $q->where('code', $code)->orWhere('card_number', $code);
                })
                ->whereNotIn('workflow_status', [
                    LibraryCard::WORKFLOW_CANCELLED,
                    LibraryCard::WORKFLOW_REJECTED,
                    LibraryCard::WORKFLOW_REVOKED,
                ])
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if ($card === null) {
                return null;
            }

            $params = $card->params ?? [];
            $params['account_link'] = array_merge($params['account_link'] ?? [], [
                'linked_at' => now()->toIso8601String(),
                'linked_user_id' => $user->id,
            ]);
            $card->user_id = $user->id;
            $card->params = $params;
            $card->save();

            return $card->fresh();
        });
    }
}
