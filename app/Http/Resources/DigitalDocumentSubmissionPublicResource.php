<?php

namespace App\Http\Resources;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Payload catalog công khai — không lộ email người nộp, đường dẫn file gốc.
 */
class DigitalDocumentSubmissionPublicResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var FilesystemAdapter $mediaStorage */
        $mediaStorage = Storage::disk((string) config('filesystems.media_disk', 'public'));
        $submissionCoverUrl = $this->cover_image_path ? $mediaStorage->url($this->cover_image_path) : null;

        $approvedBook = $this->resource->relationLoaded('approvedBook') ? $this->approvedBook : null;
        $approvedBookData = null;
        if ($approvedBook) {
            $coverImage = $approvedBook->cover_image;
            if (! empty($coverImage) && ! str_starts_with((string) $coverImage, 'http')) {
                $coverImage = $mediaStorage->url((string) $coverImage);
            }
            $approvedBookData = [
                'id' => $approvedBook->id,
                'book_code' => $approvedBook->book_code,
                'title' => $approvedBook->title,
                'summary' => $approvedBook->summary,
                'cover_image' => $coverImage,
                'authors_label' => $approvedBook->authors_label,
            ];
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'author_names' => $this->author_names,
            'description' => $this->description,
            'original_name' => $this->original_name,
            'mime' => $this->mime,
            'byte_size' => $this->byte_size,
            'status' => $this->status,
            'approved_book_id' => $this->approved_book_id,
            'approved_book' => $approvedBookData,
            'cover_image_url' => $submissionCoverUrl,
            'submitted_at' => $this->created_at?->toIso8601String(),
            'submitter' => $this->whenLoaded('submitter', fn () => [
                'name' => $this->submitter->name,
            ]),
        ];
    }
}
