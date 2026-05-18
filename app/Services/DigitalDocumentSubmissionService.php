<?php

namespace App\Services;

use App\Enums\AccessMode;
use App\Enums\ResourceType;
use App\Enums\RoleType;
use App\Enums\UploadDirectory;
use App\Helpers\FileHelpers;
use App\Models\Author;
use App\Models\Book;
use App\Models\DigitalAsset;
use App\Models\DigitalDocumentSubmission;
use App\Models\User;
use App\Services\Notifications\DigitalDocumentSubmissionNotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DigitalDocumentSubmissionService
{
    private function mediaDisk(): string
    {
        return (string) config('filesystems.media_disk', 'public');
    }

    public function __construct(
        private DigitalAssetService $digitalAssetService,
        private DigitalDocumentSubmissionNotificationService $submissionNotificationService,
    ) {}

    /**
     * Độc giả (sinh viên, giảng viên, thành viên, …) gửi tài liệu qua trang dịch vụ — luôn {@see DigitalDocumentSubmission::STATUS_PENDING}.
     * Thủ thư / quản trị không dùng luồng này (thêm đầu mục từ trang quản trị).
     */
    public function submitAsReaderPending(User $user, array $attrs, UploadedFile $file, ?UploadedFile $coverImage = null): DigitalDocumentSubmission
    {
        if ($this->isStaff($user)) {
            throw ValidationException::withMessages([
                'role' => __('Tài khoản thủ thư hoặc quản trị vui lòng quản lý tài liệu số từ trang quản trị, không gửi qua kênh độc giả.'),
            ]);
        }

        return $this->submitPendingUpload($user, $attrs, $file, $coverImage);
    }

    /**
     * Lưu file và tạo bản ghi yêu cầu ở trạng thái chờ duyệt (dùng nội bộ / test).
     */
    public function submit(User $user, array $attrs, UploadedFile $file, ?UploadedFile $coverImage = null): DigitalDocumentSubmission
    {
        return $this->submitPendingUpload($user, $attrs, $file, $coverImage);
    }

    private function submitPendingUpload(User $user, array $attrs, UploadedFile $file, ?UploadedFile $coverImage = null): DigitalDocumentSubmission
    {
        $path = FileHelpers::storeUploadedFile($file, $this->mediaDisk(), UploadDirectory::digitalSubmissionFiles());

        $coverPath = null;
        if ($coverImage !== null) {
            $coverPath = FileHelpers::storeUploadedFile(
                $coverImage,
                $this->mediaDisk(),
                UploadDirectory::digitalSubmissionCovers()
            );
        }

        $submission = DigitalDocumentSubmission::query()->create([
            'submitted_by' => $user->id,
            'title' => trim((string) ($attrs['title'] ?? '')),
            'author_names' => trim((string) ($attrs['author_names'] ?? '')),
            'description' => $attrs['description'] ?? null,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'byte_size' => $file->getSize(),
            'cover_image_path' => $coverPath,
            'status' => DigitalDocumentSubmission::STATUS_PENDING,
        ]);

        $this->submissionNotificationService->notifyStaffPendingReview();

        return $submission;
    }

    /**
     * @param  array{status?:string|null,keyword?:string|null,sort?:string|null}  $filters
     */
    public function paginateForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->baseListQuery($user);
        $this->applyListFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * @param  array{keyword?:string|null,sort?:string|null}  $filters
     */
    public function paginatePublicApproved(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DigitalDocumentSubmission::query()
            ->with($this->listPublicRelations())
            ->where('status', DigitalDocumentSubmission::STATUS_APPROVED)
            ->whereHas('approvedBook', fn (Builder $q) => $q->where('resource_type', ResourceType::DIGITAL->value));

        $this->applyListFilters($query, $filters, applyStatusFilter: false);

        return $query->paginate($perPage);
    }

    /**
     * @return Builder<DigitalDocumentSubmission>
     */
    private function baseListQuery(User $user): Builder
    {
        $query = DigitalDocumentSubmission::query()->with($this->listRelations());

        if (! $this->isStaff($user)) {
            $query->where('submitted_by', $user->id)
                ->whereNull('user_hidden_at');
        }

        return $query;
    }

    /**
     * @return list<string>
     */
    private function listRelations(): array
    {
        return [
            'submitter:id,name,email',
            'reviewer:id,name',
            'approvedBook:id,book_code,title,summary,cover_image',
            'approvedBook.authors:id,name',
        ];
    }

    /**
     * Quan hệ cho API catalog công khai (không tải email người nộp).
     *
     * @return list<string>
     */
    private function listPublicRelations(): array
    {
        return [
            'submitter:id,name',
            'approvedBook:id,book_code,title,summary,cover_image',
            'approvedBook.authors:id,name',
        ];
    }

    /**
     * @param  Builder<DigitalDocumentSubmission>  $query
     * @param  array{status?:string|null,keyword?:string|null,sort?:string|null}  $filters
     */
    private function applyListFilters(Builder $query, array $filters, bool $applyStatusFilter = true): void
    {
        if ($applyStatusFilter) {
            $status = $filters['status'] ?? null;
            if ($status !== null && $status !== '' && in_array($status, [
                DigitalDocumentSubmission::STATUS_PENDING,
                DigitalDocumentSubmission::STATUS_APPROVED,
                DigitalDocumentSubmission::STATUS_REJECTED,
            ], true)) {
                $query->where('status', $status);
            }
        }

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $like = '%'.$keyword.'%';
            $query->where(function (Builder $sub) use ($like): void {
                $sub->where('title', 'like', $like)
                    ->orWhere('author_names', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('original_name', 'like', $like)
                    ->orWhereHas('submitter', fn (Builder $q) => $q
                        ->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like))
                    ->orWhereHas('approvedBook', fn (Builder $q) => $q
                        ->where('book_code', 'like', $like)
                        ->orWhere('title', 'like', $like));
            });
        }

        $sort = (string) ($filters['sort'] ?? 'newest');
        if ($sort === 'oldest') {
            $query->orderBy('id');
        } else {
            $query->latest('id');
        }
    }

    public function approve(User $reviewer, int $id, ?string $note = null): DigitalDocumentSubmission
    {
        if (! $this->isStaff($reviewer)) {
            abort(403);
        }

        return DB::transaction(function () use ($reviewer, $id, $note) {
            /** @var DigitalDocumentSubmission $submission */
            $submission = DigitalDocumentSubmission::query()->lockForUpdate()->findOrFail($id);
            if ($submission->status !== DigitalDocumentSubmission::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'status' => __('Yêu cầu này đã được xử lý trước đó.'),
                ]);
            }

            $book = Book::query()->create([
                'title' => $submission->title,
                'summary' => $submission->description,
                'resource_type' => ResourceType::DIGITAL->value,
                'access_mode' => AccessMode::OnlineOnly->value,
                'quantity' => 0,
            ]);
            $this->syncSubmissionAuthorsToBook($book, $submission->author_names);

            $submissionDisk = $this->mediaDisk();
            $digitalDisk = FileHelpers::digitalAssetsDisk();
            if (! Storage::disk($submissionDisk)->exists($submission->file_path)) {
                throw ValidationException::withMessages([
                    'file' => __('Không tìm thấy file PDF để duyệt.'),
                ]);
            }

            $targetPath = UploadDirectory::digitalAssetsByBookId((int) $book->id).'/'.basename($submission->file_path);
            FileHelpers::copyStorageObject($submissionDisk, $submission->file_path, $digitalDisk, $targetPath);
            $checksum = FileHelpers::hashSha256FromStorage($digitalDisk, $targetPath);

            $asset = DigitalAsset::query()->create([
                'book_id' => $book->id,
                'version' => 1,
                'is_primary' => true,
                'storage_disk' => $digitalDisk,
                'path' => $targetPath,
                'original_name' => $submission->original_name,
                'mime' => $submission->mime ?: 'application/pdf',
                'byte_size' => $submission->byte_size,
                'checksum_sha256' => $checksum,
                'visibility' => 'internal',
            ]);

            $mediaDisk = $this->mediaDisk();
            if (! empty($submission->cover_image_path) && Storage::disk($submissionDisk)->exists($submission->cover_image_path)) {
                $ext = strtolower(pathinfo($submission->cover_image_path, PATHINFO_EXTENSION) ?: 'jpg');
                $ext = preg_match('/^(jpe?g|png|webp)$/i', $ext) ? $ext : 'jpg';
                $coverDest = UploadDirectory::bookCovers(ResourceType::DIGITAL->value).'/submission-'.$book->id.'.'.$ext;
                FileHelpers::copyStorageObject($submissionDisk, $submission->cover_image_path, $mediaDisk, $coverDest);
                $book->cover_image = $coverDest;
                $book->save();
            }

            $this->digitalAssetService->schedulePostUploadProcessing(
                $asset,
                $book,
                $digitalDisk,
                $targetPath,
                $submission->mime ?: 'application/pdf'
            );

            $submission->update([
                'status' => DigitalDocumentSubmission::STATUS_APPROVED,
                'review_note' => $note,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'approved_book_id' => $book->id,
            ]);

            $fresh = $submission->fresh(['submitter:id,name,email', 'reviewer:id,name', 'approvedBook:id,title']);

            $this->submissionNotificationService->notifySubmitterReviewed($fresh, approved: true);
            $this->submissionNotificationService->notifyStaffPendingReview();

            return $fresh;
        });
    }

    /**
     * Độc giả ẩn bản ghi khỏi trang "Quản lý tài liệu số" — không xóa file/ghi chú thủ thư.
     */
    public function hideFromSubmitterList(User $submitter, int $id): DigitalDocumentSubmission
    {
        /** @var DigitalDocumentSubmission $submission */
        $submission = DigitalDocumentSubmission::query()
            ->where('submitted_by', $submitter->id)
            ->whereKey($id)
            ->firstOrFail();

        $submission->update([
            'user_hidden_at' => now(),
        ]);

        return $submission->fresh();
    }

    public function reject(User $reviewer, int $id, ?string $note = null): DigitalDocumentSubmission
    {
        if (! $this->isStaff($reviewer)) {
            abort(403);
        }

        return DB::transaction(function () use ($reviewer, $id, $note) {
            /** @var DigitalDocumentSubmission $submission */
            $submission = DigitalDocumentSubmission::query()->lockForUpdate()->findOrFail($id);
            if ($submission->status !== DigitalDocumentSubmission::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'status' => __('Yêu cầu này đã được xử lý trước đó.'),
                ]);
            }

            $submission->update([
                'status' => DigitalDocumentSubmission::STATUS_REJECTED,
                'review_note' => $note,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            $fresh = $submission->fresh(['submitter:id,name,email', 'reviewer:id,name']);

            $this->submissionNotificationService->notifySubmitterReviewed($fresh, approved: false);
            $this->submissionNotificationService->notifyStaffPendingReview();

            return $fresh;
        });
    }

    private function isStaff(User $user): bool
    {
        $value = $user->user_type instanceof \BackedEnum ? $user->user_type->value : (string) $user->user_type;

        return in_array($value, RoleType::staffRoles(), true);
    }

    private function syncSubmissionAuthorsToBook(Book $book, ?string $authorNamesRaw): void
    {
        $raw = trim((string) $authorNamesRaw);
        if ($raw === '') {
            return;
        }

        $authorNames = preg_split('/[;,]+/u', $raw) ?: [];
        $authorNames = array_values(array_filter(array_map(
            static fn ($name) => trim((string) $name),
            $authorNames
        )));
        if ($authorNames === []) {
            return;
        }

        $authorSync = [];
        foreach ($authorNames as $idx => $name) {
            $author = Author::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'params' => []]
            );
            if ($author->name !== $name) {
                $author->name = $name;
                $author->save();
            }
            $authorSync[$author->id] = ['order' => $idx];
        }
        $book->authors()->sync($authorSync);
    }
}
