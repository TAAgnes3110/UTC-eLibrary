<?php

namespace App\Services;

use App\Enums\RoleType;
use App\Helpers\FileHelpers;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class UserService
{
    private const PER_PAGE = 50;

    public const ADMIN_USERS_CACHE_VERSION_KEY = 'admin:users:list-cache-version';

    public function create(array $data): User
    {
        $user = User::create($data);
        $this->bumpAdminUsersCacheVersion();

        return $user;
    }

    public function update(User $user, array $data): User
    {
        unset(
            $data['id'],
            $data['code'],
            $data['created_at'],
            $data['updated_at'],
            $data['email_verified_at'],
        );
        if (array_key_exists('password', $data) && empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);
        $this->bumpAdminUsersCacheVersion();

        return $user;
    }

    /**
     * @param  list<string>|null  $keywordColumns
     */
    public function index(
        ?string $keyword,
        bool $typeReader = false,
        int $perPage = self::PER_PAGE,
        ?array $keywordColumns = null,
        ?string $status = null,
        ?array $roleFilter = null
    ): LengthAwarePaginator {
        $query = User::query()
            ->select([
                'id',
                'code',
                'name',
                'email',
                'phone',
                'user_type',
                'avatar',
                'faculty_id',
                'period_id',
                'class_code',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->when($typeReader, fn ($q) => $q->whereIn('user_type', RoleType::readerTypes()))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'blocked', fn ($q) => $q->where('is_active', false))
            ->when(
                is_array($roleFilter) && $roleFilter !== [],
                fn ($q) => $q->whereIn('user_type', $roleFilter)
            )
            ->when($keyword !== null && $keyword !== '', function ($q) use ($keyword, $keywordColumns) {
                $keyword = trim($keyword);
                $effectiveColumns = ! empty($keywordColumns)
                    ? $keywordColumns
                    : ['name', 'email', 'code', 'phone'];
                $onlyCodePhone = array_values(array_diff($effectiveColumns, ['code', 'phone'])) === [];
                $canUsePrefixSearch = $onlyCodePhone && ! str_contains($keyword, ' ');

                $q->where(function ($sub) use ($keyword, $effectiveColumns, $canUsePrefixSearch) {
                    $applied = false;
                    if (in_array('name', $effectiveColumns, true)) {
                        $sub->where('name', 'like', "%{$keyword}%");
                        $applied = true;
                    }
                    if (in_array('email', $effectiveColumns, true)) {
                        $method = $applied ? 'orWhere' : 'where';
                        $sub->{$method}('email', 'like', "%{$keyword}%");
                        $applied = true;
                    }
                    if (in_array('code', $effectiveColumns, true)) {
                        $method = $applied ? 'orWhere' : 'where';
                        if ($canUsePrefixSearch) {
                            $sub->{$method}('code', 'like', "{$keyword}%");
                        } else {
                            $sub->{$method}('code', 'like', "%{$keyword}%");
                        }
                        $applied = true;
                    }
                    if (in_array('phone', $effectiveColumns, true)) {
                        $method = $applied ? 'orWhere' : 'where';
                        if ($canUsePrefixSearch) {
                            $sub->{$method}('phone', 'like', "{$keyword}%");
                        } else {
                            $sub->{$method}('phone', 'like', "%{$keyword}%");
                        }
                    }
                });
            })
            ->orderByDesc('id');

        return $query->paginate($perPage)->withQueryString();
    }

    public function destroy(User $user): void
    {
        $user->delete();
        $this->bumpAdminUsersCacheVersion();
    }

    public function trash(int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        return User::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function restore(int $id): ?User
    {
        $user = User::onlyTrashed()->find($id);
        if (! $user) {
            return null;
        }
        $user->restore();
        $this->bumpAdminUsersCacheVersion();

        return $user;
    }

    /** @return int số bản ghi đã khôi phục */
    public function restoreMany(array $ids): int
    {
        $ids = array_values(array_filter($ids, static fn ($v) => is_numeric($v)));
        if (empty($ids)) {
            return 0;
        }

        $restored = (int) User::onlyTrashed()->whereIn('id', $ids)->restore();
        if ($restored > 0) {
            $this->bumpAdminUsersCacheVersion();
        }

        return $restored;
    }

    public function forceDelete(int $id): bool
    {
        $user = User::onlyTrashed()->find($id);
        if (! $user) {
            return false;
        }
        $user->forceDelete();
        $this->bumpAdminUsersCacheVersion();

        return true;
    }

    /** @return int số bản ghi đã xóa vĩnh viễn */
    public function forceDeleteMany(array $ids): int
    {
        $ids = array_values(array_filter($ids, static fn ($v) => is_numeric($v)));
        if (empty($ids)) {
            return 0;
        }

        $deleted = User::onlyTrashed()->whereIn('id', $ids)->forceDelete();
        if ($deleted > 0) {
            $this->bumpAdminUsersCacheVersion();
        }

        return $deleted;
    }

    public function updateStatus(array $ids, bool $isActive): void
    {
        User::query()->whereIn('id', $ids)->update(['is_active' => $isActive]);
        $this->bumpAdminUsersCacheVersion();
    }

    /** @return array{is_active: bool}|null null nếu không tìm thấy */
    public function toggleStatus(int $id): ?array
    {
        $user = User::find($id);
        if (! $user) {
            return null;
        }
        $user->is_active = ! $user->is_active;
        $user->save();
        $this->bumpAdminUsersCacheVersion();

        return ['is_active' => $user->is_active];
    }

    /**
     * Danh sách cho trang Inertia admin — cùng query với API index (không lọc keyword/type).
     *
     * @return array{users: LengthAwarePaginator, roles: array}
     */
    public function adminList(int $perPage = 20): array
    {
        return [
            'users' => $this->index(null, false, $perPage),
            'roles' => RoleType::getRoleTypes(),
        ];
    }

    public function readers(): Collection
    {
        return User::with(['faculty:id,code,name', 'department:id,name,faculty_id', 'period:id,code,name'])
            ->whereIn('user_type', RoleType::readerTypes())
            ->get();
    }

    /**
     * @return array{avatar: string}
     */
    public function updateAvatar(User $user, UploadedFile $file): array
    {
        $path = FileHelpers::updateModelImage(
            $user,
            $file,
            'users',
            'avatar',
            $user->code ?: (string) $user->id,
            (string) config('filesystems.media_disk', 'public')
        );
        $this->bumpAdminUsersCacheVersion();

        return ['avatar' => $path];
    }

    /**
     * Bulk update avatar từ zip (file name = user.code).
     *
     * @param  list<int>|null  $onlyUserIds
     * @return array{updated:int, skipped:int, selected_count?: int, selected_missing?: int}
     */
    public function bulkUpdateAvatarFromZip(UploadedFile $zipFile, ?array $onlyUserIds = null): array
    {
        $tmpDir = FileHelpers::extractZipToTemp($zipFile, 'avatars');
        $updated = 0;
        $skipped = 0;
        $updatedUserIds = [];

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tmpDir, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $fileInfo) {
                if (! $fileInfo->isFile()) {
                    continue;
                }
                if (FileHelpers::shouldSkipZipExtractedFile($fileInfo)) {
                    $skipped++;

                    continue;
                }
                $ext = strtolower($fileInfo->getExtension() ?: '');
                if (! in_array($ext, FileHelpers::IMAGE_EXTENSIONS, true)) {
                    $skipped++;

                    continue;
                }
                $code = trim($fileInfo->getBasename('.'.$ext));
                if ($code === '') {
                    $skipped++;

                    continue;
                }
                $user = User::query()->where('code', $code)->first();
                if (! $user) {
                    $skipped++;

                    continue;
                }
                if ($onlyUserIds !== null && $onlyUserIds !== [] && ! in_array((int) $user->id, $onlyUserIds, true)) {
                    $skipped++;

                    continue;
                }

                $uploaded = new UploadedFile(
                    $fileInfo->getPathname(),
                    $fileInfo->getBasename(),
                    FileHelpers::mimeForImageExtension($ext),
                    null,
                    true
                );
                try {
                    FileHelpers::updateModelImage(
                        $user,
                        $uploaded,
                        'users',
                        'avatar',
                        $user->code ?: (string) $user->id,
                        (string) config('filesystems.media_disk', 'public')
                    );
                    $updated++;
                    $updatedUserIds[] = (int) $user->id;
                } catch (\Throwable) {
                    $skipped++;
                }
            }
        } finally {
            FileHelpers::removeDirectory($tmpDir);
        }

        if ($updated > 0) {
            $this->bumpAdminUsersCacheVersion();
        }
        $out = ['updated' => $updated, 'skipped' => $skipped];
        if ($onlyUserIds !== null && $onlyUserIds !== []) {
            $uniqueUpdated = array_values(array_unique($updatedUserIds));
            $out['selected_count'] = count($onlyUserIds);
            $out['selected_missing'] = count(array_diff($onlyUserIds, $uniqueUpdated));
        }

        return $out;
    }

    public function adminListCacheVersion(): int
    {
        if (! Cache::has(self::ADMIN_USERS_CACHE_VERSION_KEY)) {
            Cache::forever(self::ADMIN_USERS_CACHE_VERSION_KEY, 1);

            return 1;
        }

        return (int) Cache::get(self::ADMIN_USERS_CACHE_VERSION_KEY, 1);
    }

    private function bumpAdminUsersCacheVersion(): void
    {
        if (! Cache::has(self::ADMIN_USERS_CACHE_VERSION_KEY)) {
            Cache::forever(self::ADMIN_USERS_CACHE_VERSION_KEY, 1);

            return;
        }

        Cache::increment(self::ADMIN_USERS_CACHE_VERSION_KEY);
    }
}
