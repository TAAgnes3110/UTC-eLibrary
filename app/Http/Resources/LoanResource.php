<?php

namespace App\Http\Resources;

use App\Enums\LoanStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $items = $this->relationLoaded('items') ? $this->items : collect();

        return [
            'id' => $this->id,
            'loan_code' => $this->loan_code,
            'library_card_id' => $this->library_card_id,
            'library_card_number' => $this->libraryCard->card_number,
            'library_card_name' => $this->libraryCard->full_name,
            'loan_type' => $this->loan_type,
            'status' => $this->status,
            'status_label' => LoanStatus::tryFrom((string) $this->status)?->label() ?? 'Không xác định',
            'created_by_id' => $this->created_by,
            'created_by_name' => $this->whenLoaded('createdBy', fn () => $this->createdBy?->name),
            'loan_date' => $this->loan_date,
            'due_date' => $this->due_date,
            'return_date' => $this->return_date,
            'loan_items' => LoanItemResource::collection($this->whenLoaded('items')),
            'sum_fine_amount' => $this->when($this->relationLoaded('items'), (float) $items->sum('fine_amount')),
        ];
    }
}
