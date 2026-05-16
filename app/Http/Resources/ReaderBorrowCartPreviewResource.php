<?php

namespace App\Http\Resources;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** Payload tối giản cho API preview giỏ mượn — không kích hoạt authors/publishers trên model Book. */
class ReaderBorrowCartPreviewResource extends JsonResource
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
            try {
                /** @var FilesystemAdapter $mediaStorage */
                $mediaStorage = Storage::disk((string) config('filesystems.media_disk', 'public'));
                $coverImage = $mediaStorage->url($normalizedPath);
            } catch (\Throwable) {
                $coverImage = $defaultCover;
            }
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'cover_image' => $coverImage,
            'warehouse_name' => $this->whenLoaded('warehouse', fn () => $this->warehouse?->name),
            'warehouse_code' => $this->whenLoaded('warehouse', fn () => $this->warehouse?->code),
            'cabinet' => $this->cabinet,
            'available_for_borrow' => max(0, (int) ($this->available_for_borrow ?? 0)),
        ];
    }
}
