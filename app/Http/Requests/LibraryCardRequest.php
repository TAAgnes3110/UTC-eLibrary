<?php

namespace App\Http\Requests;

use App\Enums\LibraryCardStatus;
use App\Models\LibraryCard;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Dự phòng cho route tạo/sửa thẻ (nếu dùng FormRequest tách khỏi AdminLibraryCardUpdateRequest).
 */
class LibraryCardRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'period_id' => ['sometimes', 'nullable', 'integer', 'exists:periods,id'],
            'holder_type' => ['sometimes', 'nullable', 'string', Rule::in([
                LibraryCard::HOLDER_TYPE_STUDENT,
                LibraryCard::HOLDER_TYPE_TEACHER,
                LibraryCard::HOLDER_TYPE_EXTERNAL,
            ])],
            'full_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20', 'regex:/^0[0-9]{9,10}$/'],
            'email' => ['sometimes', 'nullable', 'email', 'max:190'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', Rule::enum(LibraryCardStatus::class)],
            'payment_status' => ['sometimes', 'nullable', 'string', 'max:20'],
            'payment_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
