<?php

namespace App\Services;

use App\Enums\RoleType;
use App\Helpers\ImageUploadHelper;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SimpleTableExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        $baseName = $user->code ?: (string) $user->id;
        $path = ImageUploadHelper::storeImage($file, 'avatars', $baseName);
        $user->avatar = $path;
        $user->save();
        return ['avatar' => $path];
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

    public function exportUsers(?array $ids = null): BinaryFileResponse
    {
        $query = User::query()
            ->whereNull('deleted_at')
            ->with(['faculty:id,name,code', 'department:id,name,faculty_id']);
        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        }
        $rows = $query
            ->orderBy('id')
            ->get()
            ->map(function (User $user) {
                $statusLabel = $user->is_active ? 'Hoạt động' : 'Khóa';
                return [
                    $user->id,
                    $user->code,
                    $user->name,
                    $user->email,
                    $user->phone,
                    $user->user_type?->value ?? (string) $user->user_type,
                    $statusLabel,
                    optional($user->faculty)->name,
                    optional($user->department)->name,
                    $user->cohort,
                    optional($user->created_at)?->format('Y-m-d H:i:s'),
                    optional($user->updated_at)?->format('Y-m-d H:i:s'),
                ];
            });
        $headings = [
            'ID',
            'Mã định danh',
            'Họ tên',
            'Email',
            'Số điện thoại',
            'Loại người dùng',
            'Trạng thái',
            'Khoa',
            'Bộ môn / Lớp',
            'Khóa học',
            'Ngày tạo',
            'Ngày cập nhật',
        ];
        return Excel::download(
            new SimpleTableExport($rows, $headings),
            'danh_sach_tai_khoan.xlsx'
        );
    }

    /**
     * @return array{updated:int,skipped:int}
     */
    public function bulkUpdateAvatarFromZip(UploadedFile $zipFile): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipFile->getRealPath()) !== true) {
            throw new \InvalidArgumentException(__('Không thể đọc file zip.'));
        }
        $updated = 0;
        $skipped = 0;
        $tmpDir = storage_path('app/tmp/avatars-' . uniqid());
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (str_ends_with($entry, '/')) {
                continue;
            }
            $pathInfo = pathinfo($entry);
            $code = $pathInfo['filename'] ?? null;
            $ext = strtolower($pathInfo['extension'] ?? '');
            if (!$code || !in_array($ext, ImageUploadHelper::ALLOWED_EXTENSIONS, true)) {
                $skipped++;
                continue;
            }
            $user = User::where('code', $code)->first();
            if (!$user) {
                $skipped++;
                continue;
            }
            $contents = $zip->getFromIndex($i);
            if ($contents === false) {
                $skipped++;
                continue;
            }
            $tmpPath = $tmpDir . DIRECTORY_SEPARATOR . $code . '.' . $ext;
            file_put_contents($tmpPath, $contents);
            $uploaded = new UploadedFile(
                $tmpPath,
                basename($tmpPath),
                'image/' . $ext,
                null,
                true
            );
            try {
                $this->updateAvatar($user, $uploaded);
                $updated++;
            } catch (\Throwable) {
                $skipped++;
            }
        }
        $zip->close();
        try {
            if (is_dir($tmpDir)) {
                foreach (scandir($tmpDir) as $f) {
                    if ($f === '.' || $f === '..') {
                        continue;
                    }
                    @unlink($tmpDir . DIRECTORY_SEPARATOR . $f);
                }
                @rmdir($tmpDir);
            }
        } catch (\Throwable) {
        }
        return [
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }
}
