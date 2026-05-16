<?php

namespace App\Http\Requests;

use App\Models\LibrarySetting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class LibrarySettingsPricingRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'digital_default_pdf_download_price_vnd' => ['required', 'integer', 'min:0'],

            'loan_late_return_fine_mode' => [
                'required',
                'string',
                Rule::in([
                    LibrarySetting::LOAN_LATE_RETURN_FINE_MODE_FIXED_PER_DAY,
                    LibrarySetting::LOAN_LATE_RETURN_FINE_MODE_PERCENT_BOOK_PRICE_DAILY,
                ]),
            ],
            'loan_late_return_fine_percent_of_book' => [
                'required',
                'integer',
                Rule::in([20, 30]),
            ],
            'loan_external_borrow_fee_vnd' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'digital_default_pdf_download_price_vnd.required' => 'Vui lòng nhập giá tải PDF toàn bộ (VND).',

            'loan_late_return_fine_mode.required' => 'Vui lòng chọn cách tính phạt trễ hạn.',
            'loan_late_return_fine_mode.in' => 'Kiểu phạt trễ hạn không hợp lệ.',

            'loan_late_return_fine_percent_of_book.required' => 'Vui lòng chọn tỷ lệ % (20 hoặc 30).',
            'loan_late_return_fine_percent_of_book.in' => 'Chỉ hỗ trợ 20% hoặc 30% giá bìa mỗi ngày quá hạn (mỗi cuốn).',

            'loan_external_borrow_fee_vnd.required' => 'Vui lòng nhập phí mượn bạn đọc ngoài (VND, 0 = không thu qua cấu hình).',
        ];
    }
}
