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
        $coverImage = $this->cover_image;
        $defaultCover = asset('images/default-book-cover.png');
        if (empty($coverImage)) {
            $coverImage = $defaultCover;
        } elseif (! str_starts_with((string) $coverImage, 'http')) {
            $normalizedPath = ltrim((string) $coverImage, '/');
            if (str_starts_with($normalizedPath, 'storage/')) {
                $normalizedPath = substr($normalizedPath, 8);
            }

            $coverImage = Storage::disk('public')->exists($normalizedPath)
                ? Storage::disk('public')->url($normalizedPath)
                : $defaultCover;
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
            'classification_detail_name' => $this->whenLoaded('classificationDetail', fn () => $this->classificationDetail?->name),
            'quantity' => (int) ($this->quantity ?? 0),
            'status_label' => $this->status_label,
            'is_available' => $this->is_available,
        ];
    }

    public static function resourceTypeLabel(string $value): string
    {
        return match ($value) {
            'textbook' => 'Sách giáo khoa',
            'reference' => 'Sách tham khảo',
            'thesis' => 'Luận văn / luận án',
            'journal' => 'Tạp chí',
            'digital' => 'Tài liệu số',
            default => $value,
        };
    }
}
