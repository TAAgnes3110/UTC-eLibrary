<?php

namespace App\Http\Requests;

class ReviewUserProfileUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'review_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

