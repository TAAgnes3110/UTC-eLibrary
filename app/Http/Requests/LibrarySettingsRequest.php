<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class LibrarySettingsRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'digital_default_pdf_download_price_vnd' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'digital_default_pdf_download_price_vnd.required' => 'Vui lòng nhập giá tải PDF toàn bộ (VND).',
            'digital_default_pdf_download_price_vnd.integer' => 'Giá tải PDF phải là số nguyên.',
            'digital_default_pdf_download_price_vnd.min' => 'Giá tải PDF không được âm.',
        ];
    }
}
