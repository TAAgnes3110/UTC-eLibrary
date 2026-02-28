<?php

namespace App\Http\Requests\Api;

use App\Enums\BookType;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

/**
 * Form request validate tạo/cập nhật sách (API).
 */
class BookRequest extends BaseRequest
{
    public function rules(): array
    {
        $typeValues = BookType::values();

        return [
            'title' => ['required', 'string', 'max:500'],
            'type' => ['required', 'string', Rule::in($typeValues)],
            'isbn' => ['nullable', 'string', 'max:20'],
            'classification_code' => ['nullable', 'string', 'max:100'],
            'classification_detail' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:10'],
            'edition' => ['nullable', 'string', 'max:50'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
            'author' => ['required', 'string', 'max:255'],
            'co_authors' => ['nullable', 'string', 'max:500'],
            'publisher_id' => ['nullable', 'integer', 'exists:publishers,id'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'publication_place' => ['nullable', 'string', 'max:255'],
            'published_year' => ['nullable', 'integer', 'min:1000', 'max:2100'],
            'total_pages' => ['nullable', 'integer', 'min:0'],
            'book_size' => ['nullable', 'string', 'max:50'],
            'volume_number' => ['nullable', 'integer', 'min:0'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'string', Rule::in(['available', 'unavailable', 'processing'])],
        ];
    }
}
