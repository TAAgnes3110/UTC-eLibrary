<?php

namespace App\Services;

use App\Enums\RoleType;
use App\Helpers\ImageUploadHelper;
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
        unset($data['card_number'], $data['issue_date'], $data['expiry_date']);
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
            ->orderByDesc('id');
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

    public function forceDelete(int $id): bool
    {
        $user = User::onlyTrashed()->find($id);
        if (!$user) {
            return false;
        }
        $user->forceDelete();
        return true;
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

    /**
     * @return array{avatar: string}|null null nếu lỗi (file invalid, extension không cho phép, v.v.)
     * @throws \InvalidArgumentException
     */
    public function updateAvatar(User $user, UploadedFile $file): ?array
    {
        if (!$file->isValid()) {
            return null;
        }
        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        if (!in_array($ext, ImageUploadHelper::ALLOWED_EXTENSIONS, true)) {
            throw new \InvalidArgumentException(__('Chỉ chấp nhận ảnh: ') . implode(', ', ImageUploadHelper::ALLOWED_EXTENSIONS) . '.');
        }
        ImageUploadHelper::deleteIfExists($user->avatar);
        $path = ImageUploadHelper::storeImage($file, 'avatars', (string) $user->id);
        $user->avatar = $path;
        $user->save();
        return ['avatar' => $path];
    }

    public function adminList(int $perPage = 20): array
    {
        $users = User::query()
            ->with(['faculty:id,code,name', 'department:id,code,name,faculty_id'])
            ->orderByDesc('updated_at')
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
}
