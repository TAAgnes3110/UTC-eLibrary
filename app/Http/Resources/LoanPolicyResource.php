<?php

namespace App\Http\Resources;

use App\Models\LoanPolicy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanPolicyResource extends JsonResource
{
    /**
     * API chính sách mượn — khớp cột {@see LoanPolicy}.
     *
     * @return array{
     *     id: int,
     *     code: string,
     *     name: string,
     *     user_type: string|null,
     *     max_books: int,
     *     max_days: int,
     *     max_renewals: int,
     *     overdue_fine_per_day: numeric-string|float,
     *     allow_home: bool,
     *     allow_onsite: bool,
     *     params: array<string, mixed>|null,
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'user_type' => $this->user_type,
            'max_books' => (int) $this->max_books,
            'max_days' => (int) $this->max_days,
            'max_renewals' => (int) $this->max_renewals,
            'overdue_fine_per_day' => $this->overdue_fine_per_day,
            'allow_home' => (bool) $this->allow_home,
            'allow_onsite' => (bool) $this->allow_onsite,
            'params' => $this->params,
        ];
    }
}
