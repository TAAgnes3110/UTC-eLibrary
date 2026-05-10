<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Helpers\FileHelpers;

class BookResource extends JsonResource
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

        return [
            'available_quantity' => $this->resolveAvailableQuantity(),
            'total_quantity' => $this->resolveTotalQuantity(),
            'borrowed_quantity' => $this->resolveBorrowedQuantity(),
            'lost_quantity' => $this->resolveLostQuantity(),
            'warehouse_quantity' => $this->resolveWarehouseQuantity(),
            'real_quantity' => $this->resolveRealQuantity(),
            'circulation_status' => $this->resolveCirculationStatus(),
            'circulation_status_label' => $this->resolveCirculationStatusLabel(),
            'id' => $this->id,
            'title' => $this->title,
            'resource_type' => $this->resource_type instanceof \BackedEnum
                ? $this->resource_type->value
                : ($this->resource_type ?? 'reference'),
            'access_mode' => $this->resolveAccessMode(),
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
            'publisher_place' => $this->publisher_place,
            'cabinet' => $this->resolveCabinetForApi(),
            'cover_image' => $coverImage,
            'classification_id' => $this->classification_id,
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
            'primary_digital_asset_url' => $this->resolvePrimaryDigitalAssetUrl(),
            'loan_history' => $this->whenLoaded('loanItems', function () {
                return $this->loanItems->map(function ($item) {
                    $loan = $item->loan;
                    $card = $loan?->libraryCard;
                    $readerName = $card?->full_name
                        ?: $loan?->libraryCard?->user?->full_name
                        ?: $loan?->libraryCard?->user?->name;

                    return [
                        'loan_id' => $loan?->id,
                        'loan_code' => $loan?->loan_code,
                        'loan_status' => $loan?->status,
                        'loan_date' => $loan?->loan_date?->toDateString(),
                        'due_date' => $loan?->due_date?->toDateString(),
                        'return_date' => $loan?->return_date?->toDateString(),
                        'reader_name' => $readerName,
                        'card_number' => $card?->card_number,
                        'quantity' => (int) ($item->quantity ?? 0),
                        'condition_on_loan' => $item->condition_on_loan?->value,
                        'condition_on_return' => $item->condition_on_return?->value,
                    ];
                })->values();
            }),

            /** Meta từ bản gửi độc giả (nếu đầu mục được tạo từ duyệt gửi). */
            'digital_submission' => $this->whenLoaded('digitalDocumentSubmission', function () {
                $submission = $this->digitalDocumentSubmission;
                if ($submission === null) {
                    return null;
                }

                return [
                    'submitted_at' => $submission->created_at?->toIso8601String(),
                    'reviewed_at' => $submission->reviewed_at?->toIso8601String(),
                    'submitter' => $submission->relationLoaded('submitter') && $submission->submitter ? [
                        'id' => $submission->submitter->id,
                        'name' => $submission->submitter->name,
                        'email' => $submission->submitter->email,
                    ] : null,
                ];
            }),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }

    /** Tủ lưu trữ lấy từ cột books.cabinet. */
    private function resolveCabinetForApi(): ?string
    {
        return filled($this->cabinet) ? $this->cabinet : null;
    }

    private function resolveTotalQuantity(): int
    {
        $copiesCount = $this->copies_count;
        if ($copiesCount !== null && (int) $copiesCount > 0) {
            return max(0, (int) $copiesCount);
        }

        return max(0, (int) ($this->quantity ?? 0));
    }

    private function resolveAvailableQuantity(): int
    {
        $availableCount = $this->available_copies_count;
        $copiesCount = $this->copies_count;
        if (
            $availableCount !== null &&
            $copiesCount !== null &&
            (int) $copiesCount > 0
        ) {
            return max(0, (int) $availableCount);
        }

        return max(0, (int) ($this->quantity ?? 0));
    }

    private function resolveBorrowedQuantity(): int
    {
        return max(0, (int) ($this->borrowed_copies_count ?? 0));
    }

    private function resolveLostQuantity(): int
    {
        return max(0, (int) ($this->lost_copies_count ?? 0));
    }

    private function resolveWarehouseQuantity(): int
    {
        return max(0, (int) ($this->warehouse_copies_count ?? 0));
    }

    private function resolveRealQuantity(): int
    {
        $copiesCount = (int) ($this->copies_count ?? 0);
        if ($copiesCount <= 0) {
            return max(0, (int) ($this->quantity ?? 0));
        }

        return $this->resolveWarehouseQuantity() + $this->resolveBorrowedQuantity();
    }

    private function resolveCirculationStatus(): string
    {
        return $this->resolveRealQuantity() > 0 ? 'in_circulation' : 'out_of_circulation';
    }

    private function resolveCirculationStatusLabel(): string
    {
        return $this->resolveRealQuantity() > 0 ? 'Còn lưu hành' : 'Không lưu hành';
    }

    private function resolvePrimaryDigitalAssetUrl(): ?string
    {
        if (! $this->relationLoaded('digitalAssets')) {
            return null;
        }

        $asset = $this->digitalAssets
            ->sortByDesc(fn ($it) => (int) ($it->is_primary ?? false))
            ->first();
        if (! $asset) {
            return null;
        }

        $disk = $asset->storage_disk ?: 'public';
        if (! $asset->path) {
            return null;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $assetStorage */
        $assetStorage = Storage::disk($disk);

        return $assetStorage->url($asset->path);
    }

    private function resolveAccessMode(): string
    {
        $raw = (string) ($this->resource->getRawOriginal('access_mode') ?? '');
        $normalized = trim($raw);
        if ($normalized === 'onsite') {
            // Legacy DB value, map to current enum-compatible value.
            return 'circulation_only';
        }
        if ($normalized === '') {
            return 'circulation_only';
        }

        return $normalized;
    }
}
