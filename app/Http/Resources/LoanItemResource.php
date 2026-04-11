<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'loan_id' => $this->loan_id,
            'book_id' => $this->book_id,
            'book_title' => $this->book?->title,
            'quantity' => $this->quantity,
            'condition_on_loan' => $this->condition_on_loan,
            'condition_on_loan_label' => $this->condition_on_loan?->label(),
            'condition_on_return' => $this->condition_on_return,
            'fine_amount' => $this->fine_amount,
            'notes' => $this->notes,
        ];
    }
}
