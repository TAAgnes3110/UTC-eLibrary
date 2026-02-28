<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base FormRequest: authorize luôn true, failedValidation trả JSON 422.
 *
 * @method array all()
 * @todo Cho phép cấu hình authorize theo policy tùy request con.
 */
class BaseRequest extends FormRequest
{
    /**
     * Cho phép request (có thể override ở class con theo policy).
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Khi validation lỗi: trả JSON { status, message, errors } HTTP 422.
     *
     * @param Validator $validator
     * @return void
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message'    => __('messages.validation_error'),
            'errors'     => $validator->errors()
        ], 422));
    }
}
