<?php

namespace App\Http\Resources;

use App\Helpers\FileHelpers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class NewsPostListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $mediaDisk = (string) config('filesystems.media_disk', 'public');
        /** @var \Illuminate\Filesystem\FilesystemAdapter $mediaStorage */
        $mediaStorage = Storage::disk($mediaDisk);

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'status' => $this->status,
            'type' => $this->type,
            'thumbnail_path' => $this->thumbnail_path,
            'thumbnail_url' => $this->thumbnail_path
                ? $mediaStorage->url((string) $this->thumbnail_path)
                : FileHelpers::mediaDefaultUrl('news_thumbnail'),
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'posted_by' => $this->createdBy ? [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
                'email' => $this->createdBy->email,
            ] : null,
        ];
    }
}

