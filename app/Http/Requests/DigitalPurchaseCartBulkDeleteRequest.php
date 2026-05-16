<?php

namespace App\Http\Requests;

class DigitalPurchaseCartBulkDeleteRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'digital_asset_ids' => ['required', 'array', 'min:1', 'max:50'],
            'digital_asset_ids.*' => ['integer', 'min:1'],
        ];
    }
}
