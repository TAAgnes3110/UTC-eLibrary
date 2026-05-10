<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DigitalDocumentSubmissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $mediaStorage */
        $mediaStorage = Storage::disk((string) config('filesystems.media_disk', 'public'));
        $url = $this->file_path ? $mediaStorage->url($this->file_path) : null;
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
            'file_path' => $this->file_path,
            'file_url' => $url,
            'original_name' => $this->original_name,
            'mime' => $this->mime,
            'byte_size' => $this->byte_size,
            'status' => $this->status,
            'review_note' => $this->review_note,
            'submitted_by' => $this->submitted_by,
            'reviewed_by' => $this->reviewed_by,
            'approved_book_id' => $this->approved_book_id,
            'approved_book' => $approvedBookData,
            'cover_image_url' => $submissionCoverUrl,
            'submitted_at' => $this->created_at?->toIso8601String(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'submitter' => $this->whenLoaded('submitter', fn () => [
                'id' => $this->submitter->id,
                'name' => $this->submitter->name,
                'email' => $this->submitter->email,
            ]),
            'reviewer' => $this->whenLoaded('reviewer', fn () => [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
            ]),
        ];
    }
}
