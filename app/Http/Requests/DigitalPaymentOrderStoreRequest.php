<?php

namespace App\Http\Requests;

class DigitalPaymentOrderStoreRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'digital_asset_ids' => ['required', 'array', 'min:1', 'max:50'],
            'digital_asset_ids.*' => ['required', 'integer', 'min:1', 'distinct', 'exists:digital_assets,id'],
        ];
    }

    /**
     * @return array<int, int>
     */
    public function uniqueDigitalAssetIds(): array
    {
        $ids = $this->input('digital_asset_ids', []);
        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }
}
