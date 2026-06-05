<?php

namespace App\Http\Resources;

use App\Enums\LoanStatus;
use App\Enums\LoanType;
use App\Helpers\LoanHelper;
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
        $loanType = $this->loan_type instanceof LoanType
            ? $this->loan_type
            : LoanType::tryFrom((string) $this->loan_type);
        $status = $this->status instanceof LoanStatus
            ? $this->status
            : LoanStatus::tryFrom((string) $this->status);

        return [
            'id' => $this->id,
            'loan_code' => $this->loan_code,
            'library_card_id' => $this->library_card_id,
            'library_card_number' => $this->whenLoaded('libraryCard', fn () => $this->libraryCard?->card_number),
            'library_card_name' => $this->whenLoaded('libraryCard', fn () => $this->libraryCard?->full_name),
            'loan_type' => $loanType?->value ?? (string) $this->loan_type,
            'loan_type_label' => $loanType?->label(),
            'status' => $status?->value ?? (string) $this->status,
            'status_label' => $status?->label() ?? 'Không xác định',
            'created_by_id' => $this->created_by,
            'created_by_name' => $this->whenLoaded('createdBy', fn () => $this->createdBy?->name),
            'loan_date' => $this->loan_date,
            'due_date' => $this->due_date,
            'return_date' => $this->return_date,
            'loan_items' => LoanItemResource::collection($this->whenLoaded('items')),
            'sum_fine_amount' => $this->when($this->relationLoaded('items'), (float) $items->sum('fine_amount')),
            'fine_policy' => $this->when(
                $this->shouldIncludeFinePolicy($request),
                function () {
                    try {
                        $helper = app(LoanHelper::class);
                        $policy = $helper->resolvePolicyForCard($this->libraryCard);

                        return $helper->finePolicySnapshot($policy);
                    } catch (\Throwable) {
                        return null;
                    }
                }
            ),
        ];
    }

    /** Chỉ trả fine_policy ở chi tiết phiếu — list index không cần và tránh 500 khi thiếu policy. */
    private function shouldIncludeFinePolicy(Request $request): bool
    {
        if (! $this->relationLoaded('libraryCard') || $this->libraryCard === null) {
            return false;
        }

        return (bool) preg_match('#^api/v1/loans/\d+$#', $request->path());
    }
}
