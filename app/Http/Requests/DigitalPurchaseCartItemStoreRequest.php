<?php

namespace App\Http\Requests;

class DigitalPurchaseCartItemStoreRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'digital_asset_id' => ['required', 'integer', 'min:1'],
            'book_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'book_title' => ['sometimes', 'nullable', 'string', 'max:500'],
            'file_name' => ['sometimes', 'nullable', 'string', 'max:500'],
            'cover_image' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
