<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DigitalAssetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $disk = $this->storage_disk ?: 'public';
        $url = null;
        if ($this->path && Storage::disk($disk)->exists($this->path)) {
            $url = Storage::disk($disk)->url($this->path);
        }

        return [
            'id' => $this->id,
            'book_id' => $this->book_id,
            'version' => $this->version,
            'is_primary' => $this->is_primary,
            'storage_disk' => $this->storage_disk,
            'path' => $this->path,
            'url' => $url,
            'original_name' => $this->original_name,
            'mime' => $this->mime,
            'byte_size' => $this->byte_size,
            'checksum_sha256' => $this->checksum_sha256,
            'visibility' => $this->visibility,
            'embargo_until' => $this->embargo_until?->toDateString(),
            'params' => $this->params ?? [],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
