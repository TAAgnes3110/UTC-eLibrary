<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BookResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $coverImage = $this->cover_image;
        if (! empty($coverImage) && ! str_starts_with($coverImage, 'http')) {
            $coverImage = Storage::disk('public')->exists($coverImage)
                ? asset(ltrim($coverImage, '/'))
                : null;
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'resource_type' => $this->resource_type instanceof \BackedEnum
                ? $this->resource_type->value
                : ($this->resource_type ?? 'reference'),
            'access_mode' => $this->access_mode instanceof \BackedEnum
                ? $this->access_mode->value
                : ($this->access_mode ?? 'circulation_only'),
            'registration_number' => $this->registration_number,
            'book_code' => $this->book_code,
            'quantity' => $this->quantity,
            'status_label' => $this->status_label,
            'is_available' => $this->is_available,
            'authors_label' => $this->authors_label,
            'publishers_label' => $this->publishers_label,
            'classification' => $this->whenLoaded('classification', fn () => $this->classification ? [
                'id' => $this->classification->id,
                'code' => $this->classification->code,
                'name' => $this->classification->name,
            ] : null),

            'classification_detail' => $this->whenLoaded('classificationDetail', fn () => $this->classificationDetail ? [
                'id' => $this->classificationDetail->id,
                'code' => $this->classificationDetail->code,
                'name' => $this->classificationDetail->name,
                'classification_id' => $this->classificationDetail->classification_id,
            ] : null),

            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->warehouse ? [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
            ] : null),

            'authors' => $this->whenLoaded('authors', fn () => $this->authors->map(function ($author) {
                return [
                    'id' => $author->id,
                    'name' => $author->name,
                    'order' => $author->pivot?->order,
                ];
            })),

            'publishers' => $this->whenLoaded('publishers', fn () => $this->publishers->map(function ($publisher) {
                return [
                    'id' => $publisher->id,
                    'name' => $publisher->name,
                    'order' => $publisher->pivot?->order,
                ];
            })),
            'sub_title' => $this->sub_title,
            'language' => $this->language,
            'edition' => $this->edition,
            'published_year' => $this->published_year,
            'pages' => $this->pages,
            'illustration_pages' => $this->illustration_pages,
            'book_size' => $this->book_size,
            'price' => $this->price,
            'summary' => $this->summary,
            'notes' => $this->notes,
            'series_name' => $this->series_name,
            'publisher_place' => $this->publisher_place,
            'cabinet' => $this->cabinet,
            'shelf' => $this->shelf,
            'cover_image' => $coverImage,
            'classification_id' => $this->classification_id,
            'classification_detail_id' => $this->classification_detail_id,
            'warehouse_id' => $this->warehouse_id,

            'params' => $this->params ?? [],

            'thesis_metadata' => $this->whenLoaded('thesisMetadata', fn () => $this->thesisMetadata ? [
                'work_type' => $this->thesisMetadata->work_type,
                'degree_program' => $this->thesisMetadata->degree_program,
                'supervisor_name' => $this->thesisMetadata->supervisor_name,
                'supervisor_user_id' => $this->thesisMetadata->supervisor_user_id,
                'defense_year' => $this->thesisMetadata->defense_year,
                'keywords' => $this->thesisMetadata->keywords,
                'abstract_text' => $this->thesisMetadata->abstract_text,
                'params' => $this->thesisMetadata->params ?? [],
            ] : null),

            'digital_assets' => $this->whenLoaded('digitalAssets', fn () => DigitalAssetResource::collection($this->digitalAssets)),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
