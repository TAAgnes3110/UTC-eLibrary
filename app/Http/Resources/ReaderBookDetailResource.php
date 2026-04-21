<?php

namespace App\Http\Resources;

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

            $coverImage = Storage::disk('public')->exists($normalizedPath)
                ? Storage::disk('public')->url($normalizedPath)
                : $defaultCover;
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
            'access_mode' => $this->access_mode instanceof \BackedEnum
                ? $this->access_mode->value
                : ($this->access_mode ?? 'circulation_only'),
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
            'shelf' => $this->shelf,
            'quantity' => (int) ($this->quantity ?? 0),
            'status_label' => $this->status_label,
            'is_available' => $this->is_available,
            'classification' => $this->whenLoaded('classification', fn () => $this->classification ? [
                'id' => $this->classification->id,
                'code' => $this->classification->code,
                'name' => $this->classification->name,
            ] : null),
            'classification_detail' => $this->whenLoaded('classificationDetail', fn () => $this->classificationDetail ? [
                'id' => $this->classificationDetail->id,
                'code' => $this->classificationDetail->code,
                'name' => $this->classificationDetail->name,
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
            'digital_assets' => $this->whenLoaded('digitalAssets', fn () => DigitalAssetResource::collection($this->digitalAssets)),
            'thesis_metadata' => $this->whenLoaded('thesisMetadata', fn () => $this->thesisMetadata ? [
                'keywords' => $this->thesisMetadata->keywords,
                'abstract_text' => $this->thesisMetadata->abstract_text,
            ] : null),
        ];
    }
}
