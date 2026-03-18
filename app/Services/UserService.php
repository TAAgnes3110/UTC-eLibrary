<?php

namespace App\Services;

use App\Enums\RoleType;
use App\Helpers\FileHelpers;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class UserService
{
    private const PER_PAGE = 50;

    /**
     * @param array $data
     * @return User
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * @param User $user
     * @param array $data
     * @return User
     */
    public function update(User $user, array $data): User
    {
        unset(
            $data['id'],
            $data['code'],
            $data['created_at'],
            $data['updated_at'],
            $data['email_verified_at'],
            $data['card_number'],
            $data['issue_date'],
            $data['expiry_date']
        );
        if (array_key_exists('password', $data) && empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);
        return $user;
    }

    public function index(?string $keyword, bool $typeReader = false, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        $query = User::query()
            ->with(['faculty:id,code,name', 'department:id,name,faculty_id'])
            ->when($typeReader, fn ($q) => $q->whereIn('user_type', RoleType::readerTypes()))
            ->when($keyword !== null && $keyword !== '', fn ($q) => $q->where(function ($q) use ($keyword) {
                $q->where('id', 'like', '%' . $keyword . '%')
                    ->orWhere('name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%");
            }))
            ->orderByDesc('name');
        return $query->paginate($perPage)->withQueryString();
    }

    public function destroy(User $user): void
    {
        $user->delete();
    }

    public function trash(int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        return User::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @return User|null */
    public function restore(int $id): ?User
    {
        $user = User::onlyTrashed()->find($id);
        if (!$user) {
            return null;
        }
        $user->restore();
        return $user;
    }

    /** @return int số bản ghi đã khôi phục */
    public function restoreMany(array $ids): int
    {
        $ids = array_values(array_filter($ids, static fn ($v) => is_numeric($v)));
        if (empty($ids)) {
            return 0;
        }
        return (int) User::onlyTrashed()->whereIn('id', $ids)->restore();
    }

    public function forceDelete(int $id): bool
    {
        $user = User::onlyTrashed()->find($id);
        if (!$user) {
            return false;
        }
        $user->forceDelete();
        return true;
    }

    /** @return int số bản ghi đã xóa vĩnh viễn */
    public function forceDeleteMany(array $ids): int
    {
        $ids = array_values(array_filter($ids, static fn ($v) => is_numeric($v)));
        if (empty($ids)) {
            return 0;
        }
        return User::onlyTrashed()->whereIn('id', $ids)->forceDelete();
    }

    public function updateStatus(array $ids, bool $isActive): void
    {
        User::query()->whereIn('id', $ids)->update(['is_active' => $isActive]);
    }

    /** @return array{is_active: bool}|null null nếu không tìm thấy */
    public function toggleStatus(int $id): ?array
    {
        $user = User::find($id);
        if (!$user) {
            return null;
        }
        $user->is_active = !$user->is_active;
        $user->save();
        return ['is_active' => $user->is_active];
    }

    public function adminList(int $perPage = 20): array
    {
        $users = User::query()
            ->with(['faculty:id,code,name', 'department:id,code,name,faculty_id'])
            ->orderByDesc('name')
            ->paginate($perPage);
        return [
            'users' => $users,
            'roles' => RoleType::getRoleTypes(),
        ];
    }

    /** @return \Illuminate\Database\Eloquent\Collection */
    public function readers(): \Illuminate\Database\Eloquent\Collection
    {
        return User::with(['faculty:id,code,name', 'department:id,name,faculty_id'])
            ->whereIn('user_type', RoleType::readerTypes())
            ->get();
    }

    /**
     * @return array{avatar: string}
     */
    public function updateAvatar(User $user, UploadedFile $file): array
    {
        $path = FileHelpers::updateModelImage($user, $file, 'users', 'avatar', $user->code ?: (string) $user->id);
        return ['avatar' => $path];
    }

    /**
     * Bulk update avatar từ zip (file name = user.code).
     *
     * @return array{updated:int,skipped:int}
     */
    public function bulkUpdateAvatarFromZip(UploadedFile $zipFile): array
    {
        $tmpDir = FileHelpers::extractZipToTemp($zipFile, 'avatars');
        $updated = 0;
        $skipped = 0;

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tmpDir, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile()) {
                    continue;
                }
                $ext = strtolower($fileInfo->getExtension() ?: '');
                if (!in_array($ext, FileHelpers::IMAGE_EXTENSIONS, true)) {
                    $skipped++;
                    continue;
                }
                $code = $fileInfo->getBasename('.' . $ext);
                if ($code === '') {
                    $skipped++;
                    continue;
                }
                $user = User::query()->where('code', $code)->first();
                if (!$user) {
                    $skipped++;
                    continue;
                }

                $uploaded = new UploadedFile(
                    $fileInfo->getPathname(),
                    $fileInfo->getBasename(),
                    'image/' . $ext,
                    null,
                    true
                );
                try {
                    FileHelpers::updateModelImage($user, $uploaded, 'users', 'avatar', $user->code ?: (string) $user->id);
                    $updated++;
                } catch (\Throwable) {
                    $skipped++;
                }
            }
        } finally {
            FileHelpers::removeDirectory($tmpDir);
        }

        return ['updated' => $updated, 'skipped' => $skipped];
    }
}
