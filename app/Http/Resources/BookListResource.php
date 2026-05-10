<?php

namespace App\Http\Resources;

use App\Helpers\FileHelpers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BookListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $coverImage = $this->cover_image;
        if (! empty($coverImage) && ! str_starts_with((string) $coverImage, 'http')) {
            /** @var \Illuminate\Filesystem\FilesystemAdapter $mediaStorage */
            $mediaStorage = Storage::disk((string) config('filesystems.media_disk', 'public'));
            $coverImage = $mediaStorage->url((string) $coverImage);
        }
        if (empty($coverImage)) {
            $coverImage = FileHelpers::mediaDefaultUrl('book_cover');
        }

        $quantity = max(0, (int) ($this->quantity ?? 0));

        return [
            'id' => $this->id,
            'title' => $this->title,
            'book_code' => $this->book_code,
            'registration_number' => $this->registration_number,
            'resource_type' => $this->resource_type instanceof \BackedEnum
                ? $this->resource_type->value
                : ($this->resource_type ?? 'reference'),
            'authors_label' => $this->authors_label,
            'publishers_label' => $this->publishers_label,
            'summary' => $this->summary,
            'quantity' => $quantity,
            'real_quantity' => $quantity,
            'circulation_status' => $quantity > 0 ? 'in_circulation' : 'out_of_circulation',
            'circulation_status_label' => $quantity > 0 ? 'Còn lưu hành' : 'Không lưu hành',
            'cover_image' => $coverImage,
            'cabinet' => filled($this->cabinet) ? $this->cabinet : null,
            'classification_id' => $this->classification_id,
            'warehouse_id' => $this->warehouse_id,
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
            'primary_digital_asset_url' => $this->resolvePrimaryDigitalAssetUrl(),
            'digital_assets' => $this->whenLoaded('digitalAssets', fn () => DigitalAssetResource::collection($this->digitalAssets)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function resolvePrimaryDigitalAssetUrl(): ?string
    {
        if (! $this->relationLoaded('digitalAssets')) {
            return null;
        }

        $asset = $this->digitalAssets
            ->sortByDesc(fn ($it) => (int) ($it->is_primary ?? false))
            ->first();
        if (! $asset || ! $asset->path) {
            return null;
        }

        $disk = $asset->storage_disk ?: 'public';
        /** @var \Illuminate\Filesystem\FilesystemAdapter $assetStorage */
        $assetStorage = Storage::disk($disk);

        return $assetStorage->url($asset->path);
    }
}

