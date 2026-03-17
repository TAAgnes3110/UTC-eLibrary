<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\UploadDirectory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadHelper
{
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public const MAX_SIZE_MB = 10;

    /**
     * Lưu một ảnh upload vào thư mục cho trước, tự sinh tên file.
     * Extension lấy từ file gốc, chuẩn hóa về jpg/png/gif/webp.
     *
     * @param UploadedFile $file File ảnh từ request
     * @param string $directory Thư mục trong storage/app/public
     * @param string|null $prefix Tiền tố tên file
     * @return string Đường dẫn tương đối để lưu DB
     * @throws \InvalidArgumentException Nếu không phải ảnh hoặc vượt dung lượng
     */
    public static function storeImage(UploadedFile $file, string $directory, ?string $prefix = null): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            $ext = 'jpg';
        }
        if ($file->getSize() > self::MAX_SIZE_MB * 1024 * 1024) {
            throw new \InvalidArgumentException('File vượt quá ' . self::MAX_SIZE_MB . 'MB.');
        }
        $directory = trim($directory, '/');
        $name = $prefix
            ? $prefix . '.' . $ext
            : Str::uuid()->toString() . '.' . $ext;
        $path = $file->storeAs($directory, $name, 'public');
        return $path;
    }

    /**
     * Xóa ảnh cũ nếu tồn tại.
     * Bỏ qua nếu path rỗng hoặc là URL ngoài .
     */
    public static function deleteIfExists(?string $path): void
    {
        if (empty($path) || str_starts_with($path, 'http')) {
            return;
        }
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Cập nhật một trường ảnh cho model:
     * - Xóa file cũ nếu có
     * - Lưu file mới vào thư mục: upload/{table}
     * - Gán path mới vào $attribute và save model
     *
     * @param Model $model    Model Eloquent cần cập nhật
     * @param UploadedFile $file  File upload
     * @param string $table       Tên bảng (vd: users, books, warehouses, documents, ...)
     * @param string $attribute   Tên field lưu path ảnh trên model (vd: avatar, cover_image)
     * @param string|null $baseName Tên file cơ sở (mặc định: code hoặc id)
     * @return string Đường dẫn ảnh đã lưu
     */
    public static function updateModelImage(
        Model $model,
        UploadedFile $file,
        string $table,
        string $attribute,
        ?string $baseName = null
    ): string {
        self::deleteIfExists($model->{$attribute});

        $baseName ??= $model->code ?? (string) $model->id;
        $directory = UploadDirectory::forTable($table);

        $path = self::storeImage($file, $directory, $baseName);
        $model->{$attribute} = $path;
        $model->save();

        return $path;
    }
}
