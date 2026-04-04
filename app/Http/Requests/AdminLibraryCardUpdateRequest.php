<?php

namespace App\Http\Requests;

use App\Enums\LibraryCardStatus;
use App\Models\LibraryCard;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class AdminLibraryCardUpdateRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'workflow_status' => ['sometimes', 'nullable', 'string', 'max:32', Rule::in([
                LibraryCard::WORKFLOW_DRAFT,
                LibraryCard::WORKFLOW_PENDING_PAYMENT,
                LibraryCard::WORKFLOW_PENDING_REVIEW,
                LibraryCard::WORKFLOW_ACTIVE,
                LibraryCard::WORKFLOW_REJECTED,
                LibraryCard::WORKFLOW_CANCELLED,
                LibraryCard::WORKFLOW_EXPIRED,
                LibraryCard::WORKFLOW_REVOKED,
            ])],
            'payment_status' => ['sometimes', 'nullable', 'string', 'max:20', Rule::in([
                LibraryCard::PAYMENT_PENDING,
                LibraryCard::PAYMENT_PAID,
                LibraryCard::PAYMENT_FAILED,
                LibraryCard::PAYMENT_REFUNDED,
            ])],
            'payment_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'paid_at' => ['sometimes', 'nullable', 'date'],
            'payment_method' => ['sometimes', 'nullable', 'string', 'max:40'],
            'receipt_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'payment_collected_by' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'reviewed_by' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'reviewed_at' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'holder_type' => ['sometimes', 'nullable', 'string', Rule::in([
                LibraryCard::HOLDER_TYPE_STUDENT,
                LibraryCard::HOLDER_TYPE_TEACHER,
                LibraryCard::HOLDER_TYPE_EXTERNAL,
            ])],
            'card_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'full_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'period_id' => ['sometimes', 'nullable', 'integer', 'exists:periods,id'],
            'faculty_id' => ['sometimes', 'nullable', 'integer', 'exists:faculties,id'],
            'department_id' => ['sometimes', 'nullable', 'integer', 'exists:departments,id'],
            'class_code' => ['sometimes', 'nullable', 'string', 'max:80'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'email' => ['sometimes', 'nullable', 'email', 'max:190'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', Rule::enum(LibraryCardStatus::class)],
            'is_active' => ['sometimes', 'boolean'],
            'issue_date' => ['sometimes', 'nullable', 'date'],
            'expiry_date' => ['sometimes', 'nullable', 'date'],
            'issued_by' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'revoked_at' => ['sometimes', 'nullable', 'date'],
            'revoked_reason' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'params' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
