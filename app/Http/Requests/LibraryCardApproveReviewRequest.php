<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Thủ thư duyệt hồ sơ chờ xác nhận → kích hoạt thẻ (workflow active, trạng thái hoạt động).
 */
class LibraryCardApproveReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [];
    }
}
