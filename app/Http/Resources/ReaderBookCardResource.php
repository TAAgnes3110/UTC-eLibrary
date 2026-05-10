<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ReaderBookCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource === null) {
            return [];
        }

        $coverImage = $this->cover_image;
        $defaultCover = asset('images/default-book-cover.png');
        if (empty($coverImage)) {
            $coverImage = $defaultCover;
        } elseif (! str_starts_with((string) $coverImage, 'http')) {
            $normalizedPath = ltrim((string) $coverImage, '/');
            if (str_starts_with($normalizedPath, 'storage/')) {
                $normalizedPath = substr($normalizedPath, 8);
            }
            /** @var \Illuminate\Filesystem\FilesystemAdapter $mediaStorage */
            $mediaStorage = Storage::disk((string) config('filesystems.media_disk', 'public'));
            $coverImage = $mediaStorage->url($normalizedPath);
        }

        $rt = $this->resource_type instanceof \BackedEnum
            ? $this->resource_type->value
            : ($this->resource_type ?? 'reference');

        return [
            'id' => $this->id,
            'book_code' => $this->book_code,
            'title' => $this->title,
            'cover_image' => $coverImage,
            'authors_label' => $this->authors_label,
            'publishers_label' => $this->publishers_label,
            'resource_type' => $rt,
            'resource_type_label' => self::resourceTypeLabel($rt),
            'classification_name' => $this->whenLoaded('classification', fn () => $this->classification?->name),
            'warehouse_name' => $this->whenLoaded('warehouse', fn () => $this->warehouse?->name),
            'warehouse_code' => $this->whenLoaded('warehouse', fn () => $this->warehouse?->code),
            'cabinet' => $this->cabinet,
            'quantity' => (int) ($this->quantity ?? 0),
            'on_loan_total_count' => (int) ($this->on_loan_total_count ?? 0),
            'reserved_pending_count' => (int) ($this->reserved_pending_count ?? 0),
            'available_for_borrow' => max(0, (int) ($this->available_for_borrow ?? 0)),
            'status_label' => $this->status_label,
            'is_available' => max(0, (int) ($this->available_for_borrow ?? 0)) > 0,
        ];
    }

    public static function resourceTypeLabel(string $value): string
    {
        return match ($value) {
            'textbook' => 'Sách giáo trình',
            'reference' => 'Sách tham khảo',
            // Legacy data: vẫn hiển thị theo nhóm tham khảo.
            'thesis', 'journal' => 'Sách tham khảo',
            'digital' => 'Tài liệu số',
            default => $value,
        };
    }
}
