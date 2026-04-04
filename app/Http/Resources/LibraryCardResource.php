<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LibraryCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payment = $this->payment;

        return [
            'id' => $this->id,
            'card_number' => $this->card_number,
            'holder_type' => $this->holder_type,
            'workflow_status' => $this->workflow_status,

            'payment_status' => $payment?->payment_status,
            'payment_amount' => $payment?->payment_amount,
            'payment_method' => $payment?->payment_method,
            'receipt_number' => $payment?->receipt_number,
            'paid_at' => $payment?->paid_at?->toIso8601String(),

            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'notes' => $this->notes,

            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'date_of_birth' => $this->date_of_birth?->toIso8601String(),

            'period_id' => $this->period_id,
            'faculty_id' => $this->faculty_id,
            'department_id' => $this->department_id,
            'class_code' => $this->class_code,

            'period' => $this->whenLoaded('period', fn () => [
                'id' => $this->period->id,
                'code' => $this->period->code,
                'name' => $this->period->name,
                'start_year' => $this->period->start_year,
                'end_year' => $this->period->end_year,
            ]),
            'faculty' => $this->whenLoaded('faculty', fn () => [
                'id' => $this->faculty->id,
                'code' => $this->faculty->code,
                'name' => $this->faculty->name,
            ]),
            'department' => $this->whenLoaded('department', fn () => [
                'id' => $this->department->id,
                'code' => $this->department->code,
                'name' => $this->department->name,
            ]),

            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'code' => $this->user->code,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone ?? null,
                'user_type' => $this->user->user_type instanceof \BackedEnum ? $this->user->user_type->value : $this->user->user_type,
            ]),
            'reviewer' => $this->whenLoaded('reviewer', fn () => [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
            ]),
            'issuer' => $this->whenLoaded('issuer', fn () => [
                'id' => $this->issuer->id,
                'name' => $this->issuer->name,
            ]),
            'payment_collector' => $payment && $payment->collector
                ? [
                    'id' => $payment->collector->id,
                    'name' => $payment->collector->name,
                ]
                : null,

            'photo_path' => $this->photo_path,
            'external_organization' => $this->external_organization,
            'code' => $this->code,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'is_active' => $this->is_active,
            'issue_date' => $this->issue_date?->toIso8601String(),
            'expiry_date' => $this->expiry_date?->toIso8601String(),
            'revoked_at' => $this->revoked_at?->toIso8601String(),
            'revoked_reason' => $this->revoked_reason,
            'params' => $this->params,
        ];
    }
}
