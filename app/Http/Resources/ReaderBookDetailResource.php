<?php

namespace App\Http\Resources;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ReaderBookDetailResource extends JsonResource
{
    /**
     * Chi tiết tra cứu công khai — không ghi nội dung nội bộ (notes) nếu không cần.
     *
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
            /** @var FilesystemAdapter $mediaStorage */
            $mediaStorage = Storage::disk((string) config('filesystems.media_disk', 'public'));
            $coverImage = $mediaStorage->url($normalizedPath);
        }

        $rt = $this->resource_type instanceof \BackedEnum
            ? $this->resource_type->value
            : ($this->resource_type ?? 'reference');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'sub_title' => $this->sub_title,
            'cover_image' => $coverImage,
            'authors_label' => $this->authors_label,
            'publishers_label' => $this->publishers_label,
            'resource_type' => $rt,
            'resource_type_label' => ReaderBookCardResource::resourceTypeLabel($rt),
            'access_mode' => $this->resolveAccessMode(),
            'registration_number' => $this->registration_number,
            'book_code' => $this->book_code,
            'language' => $this->language,
            'edition' => $this->edition,
            'published_year' => $this->published_year,
            'pages' => $this->pages,
            'book_size' => $this->book_size,
            'price' => $this->price,
            'summary' => $this->summary,
            'publisher_place' => $this->publisher_place,
            'cabinet' => $this->cabinet,
            'quantity' => (int) ($this->quantity ?? 0),
            'status_label' => $this->status_label,
            'is_available' => $this->is_available,
            'available_for_borrow' => max(0, (int) ($this->available_for_borrow ?? 0)),
            'reserved_pending_count' => (int) ($this->reserved_pending_count ?? 0),
            'classification' => $this->whenLoaded('classification', fn () => $this->classification ? [
                'id' => $this->classification->id,
                'code' => $this->classification->code,
                'name' => $this->classification->name,
            ] : null),
            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->warehouse ? [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
            ] : null),
            'authors' => $this->whenLoaded('authors', fn () => $this->authors->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
            ])),
            'publishers' => $this->whenLoaded('publishers', fn () => $this->publishers->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
            ])),
            // Luôn resolve sẵn thành mảng (tránh object JsonResource lồng nhau khi Inertia encode).
            'digital_assets' => $this->whenLoaded('digitalAssets', function () {
                return ReaderDigitalAssetResource::collection($this->digitalAssets)->resolve(request());
            }),
            'has_digital_attachment' => $this->whenLoaded('digitalAssets', function () {
                return $this->digitalAssets->contains(fn ($asset) => filled($asset->path));
            }),
            'primary_digital_asset_id' => $this->whenLoaded('digitalAssets', function () {
                $a = $this->digitalAssets->sortByDesc(fn ($it) => (int) ($it->is_primary ?? false))->first();

                return $a ? (int) $a->id : null;
            }),
            'thesis_metadata' => $this->whenLoaded('thesisMetadata', fn () => $this->thesisMetadata ? [
                'keywords' => $this->thesisMetadata->keywords,
                'abstract_text' => $this->thesisMetadata->abstract_text,
            ] : null),
        ];
    }

    private function resolveAccessMode(): string
    {
        $raw = (string) ($this->resource->getRawOriginal('access_mode') ?? '');
        $normalized = trim($raw);
        if ($normalized === 'onsite') {
            return 'circulation_only';
        }
        if ($normalized === '') {
            return 'circulation_only';
        }

        return $normalized;
    }
}
