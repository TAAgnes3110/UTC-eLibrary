<?php

namespace App\Http\Resources;

use App\Enums\LoanItemCondition;
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
            'condition_on_return_label' => $this->condition_on_return?->label(),
            'damage_percent' => $this->damage_percent,
            'book_price' => $this->book?->price !== null ? (float) $this->book->price : null,
            'fine_amount' => $this->fine_amount,
            'fine_rule_hint' => $this->fineRuleHint(),
            'notes' => $this->notes,
        ];
    }

    private function fineRuleHint(): ?string
    {
        return match ($this->condition_on_return) {
            LoanItemCondition::DAMAGED => 'Phạt hư hỏng = giá sách × (% mức hư ÷ 100); cộng phạt quá hạn nếu có.',
            LoanItemCondition::LOST => 'Mất sách = 100% mức phạt quy định (giá × hệ số + phí xử lý); cộng phạt quá hạn nếu có.',
            default => null,
        };
    }
}
