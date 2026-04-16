<?php

namespace App\Services;

use App\Helpers\FileHelpers;
use App\Models\User;
use App\Models\UserProfileUpdateRequest;
use App\Services\Notifications\UserProfileUpdateNotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UserProfileUpdateRequestService
{
    public function __construct(
        private readonly UserProfileUpdateNotificationService $userProfileUpdateNotificationService
    ) {}

    /**
     * @return Collection<int, UserProfileUpdateRequest>
     */
    public function myRequests(User $user): Collection
    {
        return UserProfileUpdateRequest::query()
            ->with([
                'user:id,name,email,code,class_code,faculty_id,period_id',
                'user.faculty:id,code,name',
                'user.period:id,code,name',
                'requestedFaculty:id,code,name',
                'requestedPeriod:id,code,name',
                'reviewer:id,name,email',
            ])
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function submit(User $user, array $payload, UploadedFile $proofImage): UserProfileUpdateRequest
    {
        $requestedCode = isset($payload['requested_code']) ? trim((string) $payload['requested_code']) : null;
        $requestedClassCode = isset($payload['requested_class_code']) ? trim((string) $payload['requested_class_code']) : null;
        $requestedFacultyId = array_key_exists('requested_faculty_id', $payload) && $payload['requested_faculty_id'] !== null
            ? (int) $payload['requested_faculty_id']
            : null;
        $requestedPeriodId = array_key_exists('requested_period_id', $payload) && $payload['requested_period_id'] !== null
            ? (int) $payload['requested_period_id']
            : null;

        $requestedCode = $requestedCode !== '' ? $requestedCode : null;
        $requestedClassCode = $requestedClassCode !== '' ? $requestedClassCode : null;

        $hasChange = false;
        if ($requestedCode !== null && $requestedCode !== (string) $user->code) {
            $hasChange = true;
        }
        if ($requestedFacultyId !== null && $requestedFacultyId !== (int) ($user->faculty_id ?? 0)) {
            $hasChange = true;
        }
        if ($requestedClassCode !== null && $requestedClassCode !== (string) ($user->class_code ?? '')) {
            $hasChange = true;
        }
        if ($requestedPeriodId !== null && $requestedPeriodId !== (int) ($user->period_id ?? 0)) {
            $hasChange = true;
        }

        if (! $hasChange) {
            throw new RuntimeException('Yêu cầu không có thay đổi hợp lệ (mã định danh/khoa/niên khóa/lớp).');
        }

        $existsPending = UserProfileUpdateRequest::query()
            ->where('user_id', $user->id)
            ->where('status', UserProfileUpdateRequest::STATUS_PENDING)
            ->exists();
        if ($existsPending) {
            throw new RuntimeException('Bạn đang có yêu cầu chờ duyệt. Vui lòng đợi xử lý trước khi gửi yêu cầu mới.');
        }

        if ($requestedCode !== null) {
            $duplicated = User::query()
                ->where('id', '!=', $user->id)
                ->where('code', $requestedCode)
                ->exists();
            if ($duplicated) {
                throw new RuntimeException('Mã định danh mới đã tồn tại trong hệ thống.');
            }
        }

        $proofPath = FileHelpers::storeUploadedFile($proofImage, 'public', 'upload/user-profile-update-requests');

        $record = UserProfileUpdateRequest::query()->create([
            'user_id' => $user->id,
            'requested_code' => $requestedCode,
            'requested_faculty_id' => $requestedFacultyId,
            'requested_period_id' => $requestedPeriodId,
            'requested_class_code' => $requestedClassCode,
            'proof_image_path' => $proofPath,
            'reason' => isset($payload['reason']) ? trim((string) $payload['reason']) : null,
            'status' => UserProfileUpdateRequest::STATUS_PENDING,
        ]);

        $this->userProfileUpdateNotificationService->notifyAdminsProfileReviewNeeded($record, $user);

        return $record;
    }

    public function adminList(?string $status, int $perPage = 20, ?string $search = null, string $sortBy = 'newest'): LengthAwarePaginator
    {
        $search = $search !== null ? trim($search) : '';
        $sortBy = $sortBy === 'oldest' ? 'oldest' : 'newest';

        $query = UserProfileUpdateRequest::query()
            ->with([
                'user:id,name,email,phone,code,class_code,faculty_id,period_id',
                'user.faculty:id,code,name',
                'user.period:id,code,name',
                'requestedFaculty:id,code,name',
                'requestedPeriod:id,code,name',
                'reviewer:id,name,email',
            ])
            ->when($status !== null && $status !== '', fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search): void {
                $like = '%'.$search.'%';
                $q->whereHas('user', function ($sub) use ($like): void {
                    $sub->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('code', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                });
            });

        $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END");

        if ($sortBy === 'oldest') {
            $query->orderBy('id');
        } else {
            $query->orderByDesc('id');
        }

        return $query
            ->paginate(min(max($perPage, 1), 100))
            ->withQueryString();
    }

    public function approve(int $requestId, ?string $reviewNote = null): UserProfileUpdateRequest
    {
        return DB::transaction(function () use ($requestId, $reviewNote): UserProfileUpdateRequest {
            $record = UserProfileUpdateRequest::query()
                ->whereKey($requestId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($record->status !== UserProfileUpdateRequest::STATUS_PENDING) {
                throw new RuntimeException('Yêu cầu đã được xử lý trước đó.');
            }

            $user = User::query()->whereKey($record->user_id)->lockForUpdate()->firstOrFail();

            if (! empty($record->requested_code)) {
                $duplicated = User::query()
                    ->where('id', '!=', $user->id)
                    ->where('code', $record->requested_code)
                    ->exists();
                if ($duplicated) {
                    throw new RuntimeException('Không thể duyệt vì mã định danh đã được sử dụng.');
                }
            }

            $updates = [];
            if (! empty($record->requested_code)) {
                $updates['code'] = $record->requested_code;
            }
            if ($record->requested_faculty_id !== null) {
                $updates['faculty_id'] = $record->requested_faculty_id;
            }
            if ($record->requested_period_id !== null) {
                $updates['period_id'] = $record->requested_period_id;
            }
            if ($record->requested_class_code !== null && $record->requested_class_code !== '') {
                $updates['class_code'] = $record->requested_class_code;
            }

            if ($updates !== []) {
                $user->update($updates);
            }

            $record->update([
                'status' => UserProfileUpdateRequest::STATUS_APPROVED,
                'review_note' => $reviewNote,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'applied_at' => now(),
            ]);

            $this->userProfileUpdateNotificationService->notifyUserProfileRequestReviewed($record, true);

            return $record->fresh(['user.faculty:id,code,name', 'user.period:id,code,name', 'requestedFaculty:id,code,name', 'requestedPeriod:id,code,name', 'reviewer:id,name,email']);
        });
    }

    public function reject(int $requestId, ?string $reviewNote = null): UserProfileUpdateRequest
    {
        return DB::transaction(function () use ($requestId, $reviewNote): UserProfileUpdateRequest {
            $record = UserProfileUpdateRequest::query()
                ->whereKey($requestId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($record->status !== UserProfileUpdateRequest::STATUS_PENDING) {
                throw new RuntimeException('Yêu cầu đã được xử lý trước đó.');
            }

            $record->update([
                'status' => UserProfileUpdateRequest::STATUS_REJECTED,
                'review_note' => $reviewNote,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            $this->userProfileUpdateNotificationService->notifyUserProfileRequestReviewed($record, false);

            return $record->fresh(['user.faculty:id,code,name', 'user.period:id,code,name', 'requestedFaculty:id,code,name', 'requestedPeriod:id,code,name', 'reviewer:id,name,email']);
        });
    }

}

