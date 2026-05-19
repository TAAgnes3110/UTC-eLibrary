<?php

namespace App\Http\Requests;

use App\Enums\AccessMode;
use App\Enums\ResourceType;
use App\Helpers\DeployHelper;
use App\Helpers\FileHelpers;

class StoreDigitalBookRequest extends BookRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'resource_type' => ResourceType::DIGITAL->value,
            'access_mode' => AccessMode::OnlineOnly->value,
            'quantity' => 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'file' => FileHelpers::pdfUploadRules(DeployHelper::maxDigitalPdfUploadKilobytes()),
            'book_cover' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'is_primary' => ['sometimes', 'boolean'],
            'visibility' => ['sometimes', 'string', 'in:public,internal,restricted'],
            'embargo_until' => ['sometimes', 'nullable', 'date'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'file.max' => __('File PDF không được vượt quá :max MB.', [
                'max' => DeployHelper::maxDigitalPdfUploadMegabytesLabel(),
            ]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function bookPayload(): array
    {
        return $this->safe()->except(['file', 'book_cover', 'is_primary', 'visibility', 'embargo_until']);
    }

    /**
     * @return array<string, mixed>
     */
    public function digitalAssetAttributes(): array
    {
        return $this->safe()->only(['is_primary', 'visibility', 'embargo_until']);
    }
}
