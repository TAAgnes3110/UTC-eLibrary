<?php

namespace App\Http\Requests;

use App\Models\NewsPost;
use App\Support\SafeHtml;

class NewsPostRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('content')) {
            $this->merge([
                'content' => SafeHtml::sanitize($this->input('content')),
            ]);
        }
    }

    public function rules(): array
    {
        $statuses = implode(',', NewsPost::statuses());
        $types = implode(',', NewsPost::types());

        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'remove_thumbnail' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', "in:{$statuses}"],
            'type' => ['nullable', 'string', "in:{$types}"],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar'],
            'remove_attachment_ids' => ['nullable', 'array'],
            'remove_attachment_ids.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'attachments.max' => __('Tối đa 10 tệp đính kèm cho mỗi bài viết.'),
            'attachments.*.max' => __('Mỗi tệp đính kèm không vượt quá 10MB.'),
            'attachments.*.mimes' => __('Định dạng tệp đính kèm không được hỗ trợ.'),
        ];
    }
}
