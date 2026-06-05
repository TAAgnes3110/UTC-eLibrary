<?php

namespace App\Http\Requests;

use App\Enums\LibraryCardStatus;
use App\Models\LibraryCard;
use App\Support\DateOfBirthRules;
use Illuminate\Validation\Rule;

class LibraryCardRequest extends BaseRequest
{
    protected function routeLibraryCard(): ?LibraryCard
    {
        $param = $this->route('library_card');

        return $param instanceof LibraryCard
            ? $param
            : ($param !== null && $param !== '' ? LibraryCard::query()->find($param) : null);
    }

    protected function prepareForValidation(): void
    {
        $existing = $this->routeLibraryCard();
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $incomingHolderType = $this->input('holder_type');
        if ($incomingHolderType !== null && $incomingHolderType !== '') {
            $this->merge(['holder_type' => (string) $incomingHolderType]);
        } elseif (! $isUpdate) {
            $resolvedHolderType = $existing
                ? (($existing->holder_type instanceof \BackedEnum)
                    ? $existing->holder_type->value
                    : (string) $existing->holder_type)
                : LibraryCard::HOLDER_TYPE_EXTERNAL;
            $this->merge(['holder_type' => $resolvedHolderType]);
        }
        if ($this->isMethod('POST') && ! $existing && ! $this->is('api/v1/library-cards/guest-register')) {
            $uid = $this->input('user_id');
            $ht = (string) $this->input('holder_type');
            if (LibraryCard::holderTypeIsFeeExempt($ht)) {
                $this->merge(['payment_amount' => 0, 'paid_at_counter' => true]);
            }
            if ($uid !== null && $uid !== '' && in_array($ht, [
                LibraryCard::HOLDER_TYPE_STUDENT,
                LibraryCard::HOLDER_TYPE_TEACHER,
            ], true)) {
                if (! $this->has('paid_at_counter')) {
                    $this->merge(['paid_at_counter' => true]);
                }
                if ($this->boolean('paid_at_counter') && ! $this->filled('payment_amount')) {
                    $this->merge(['payment_amount' => 0]);
                }
            }
        }
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return $isUpdate ? $this->updateRules() : $this->storeRules();
    }

    /**
     * Đăng ký khách / tạo mới không kèm `library_card` trong URL.
     */
    private function storeRules(): array
    {
        $holderType = (string) $this->input('holder_type');
        $isGuestRegister = $this->is('api/v1/library-cards/guest-register');

        $core = [
            'holder_type' => [
                'required',
                Rule::in([
                    LibraryCard::HOLDER_TYPE_STUDENT,
                    LibraryCard::HOLDER_TYPE_TEACHER,
                    LibraryCard::HOLDER_TYPE_EXTERNAL,
                ]),
            ],
            'user_id' => $isGuestRegister
                ? ['prohibited']
                : ['nullable', 'integer', 'exists:users,id'],
            'paid_at_counter' => ['sometimes', 'boolean'],
            'payment_amount' => ['required_if:paid_at_counter,true', 'nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'receipt_number' => ['nullable', 'string', 'max:50'],
            'photo' => ['sometimes', 'nullable', 'image', 'max:10240'],
            'code' => ['required', 'string', 'max:255', Rule::unique('library_cards', 'code')->whereNull('deleted_at')],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('library_cards', 'email')->whereNull('deleted_at')],
            'date_of_birth' => DateOfBirthRules::required(),
            'photo_path' => [Rule::requiredIf(fn () => ! $this->hasFile('photo')), 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('library_cards', 'phone')->whereNull('deleted_at')],
            'address' => ['required', 'string', 'max:1000', Rule::unique('library_cards', 'address')->whereNull('deleted_at')],
        ];

        if ($holderType === LibraryCard::HOLDER_TYPE_STUDENT) {
            $core['faculty_id'] = ['required', 'integer', 'exists:faculties,id'];
            $core['period_id'] = ['required', 'integer', 'exists:periods,id'];
            $core['class_code'] = ['required', 'string', 'max:80'];
            $core['department_id'] = ['nullable', 'integer', 'exists:departments,id'];
        } elseif ($holderType === LibraryCard::HOLDER_TYPE_TEACHER) {
            $core['faculty_id'] = ['required', 'integer', 'exists:faculties,id'];
            $core['department_id'] = ['nullable', 'integer', 'exists:departments,id'];
            $core['period_id'] = ['nullable', 'integer', 'exists:periods,id'];
            $core['class_code'] = ['nullable', 'string', 'max:80'];
        } else {
            $core['faculty_id'] = ['nullable', 'integer', 'exists:faculties,id'];
            $core['period_id'] = ['nullable', 'integer', 'exists:periods,id'];
            $core['class_code'] = ['nullable', 'string', 'max:80'];
            $core['department_id'] = ['nullable', 'integer', 'exists:departments,id'];
            $core['external_organization'] = ['nullable', 'string', 'max:150'];
        }

        $core['params'] = ['prohibited'];

        return $core;
    }

    private function updateRules(): array
    {
        $ignoreId = $this->routeLibraryCard()?->id;

        $holderIsStudent = fn () => (string) $this->input('holder_type') === LibraryCard::HOLDER_TYPE_STUDENT;
        $holderIsInternal = fn () => in_array((string) $this->input('holder_type'), [
            LibraryCard::HOLDER_TYPE_STUDENT,
            LibraryCard::HOLDER_TYPE_TEACHER,
        ], true);

        return [
            'code' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('library_cards', 'code')->ignore($ignoreId),
            ],
            'full_name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('library_cards', 'email')->ignore($ignoreId),
            ],
            'date_of_birth' => DateOfBirthRules::sometimes(),
            'photo_path' => ['sometimes', 'string', 'max:255'],
            'phone' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('library_cards', 'phone')->ignore($ignoreId),
            ],
            'address' => [
                'sometimes',
                'string',
                'max:1000',
                Rule::unique('library_cards', 'address')->ignore($ignoreId),
            ],
            'faculty_id' => [
                Rule::requiredIf($holderIsInternal),
                'nullable',
                'integer',
                'exists:faculties,id',
            ],
            'period_id' => [
                Rule::requiredIf($holderIsStudent),
                'nullable',
                'integer',
                'exists:periods,id',
            ],
            'department_id' => ['sometimes', 'nullable', 'integer', 'exists:departments,id'],
            'class_code' => [
                Rule::requiredIf($holderIsStudent),
                'nullable',
                'string',
                'max:80',
            ],
            'holder_type' => [
                'sometimes',
                'string',
                Rule::in([
                    LibraryCard::HOLDER_TYPE_STUDENT,
                    LibraryCard::HOLDER_TYPE_TEACHER,
                    LibraryCard::HOLDER_TYPE_EXTERNAL,
                ]),
            ],
            'status' => ['sometimes', 'integer', Rule::in(LibraryCardStatus::values())],
            'workflow_status' => ['prohibited'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'external_organization' => ['sometimes', 'nullable', 'string', 'max:150'],
            'issue_date' => ['sometimes', 'nullable', 'date'],
            'expiry_date' => ['sometimes', 'nullable', 'date'],
            'params' => ['sometimes', 'nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => __('Mã định danh không được để trống'),
            'code.unique' => __('Mã định danh đã được sử dụng'),
            'full_name.required' => __('Họ và tên không được để trống'),
            'email.required' => __('Email không được để trống'),
            'email.email' => __('Email không hợp lệ'),
            'email.unique' => __('Email đã được sử dụng'),
            'date_of_birth.required' => __('Ngày sinh không được để trống'),
            'date_of_birth.date' => __('Ngày sinh không hợp lệ'),
            'photo_path.required' => __('Ảnh thẻ không được để trống'),
            'photo_path.string' => __('Ảnh thẻ phải là một chuỗi'),
            'photo_path.max' => __('Ảnh thẻ không được vượt quá 255 ký tự'),
            'phone.required' => __('Số điện thoại không được để trống'),
            'phone.string' => __('Số điện thoại phải là một chuỗi'),
            'phone.max' => __('Số điện thoại không được vượt quá 20 ký tự'),
            'phone.unique' => __('Số điện thoại đã được sử dụng'),
            'address.unique' => __('Địa chỉ đã tồn tại'),
            'holder_type.in' => __('Loại thẻ không hợp lệ'),
            'faculty_id.required' => __('Khoa không được để trống'),
            'period_id.required' => __('Niên khóa không được để trống'),
            'class_code.required' => __('Lớp không được để trống'),
            'user_id.prohibited' => __('Không được gửi user_id trên đường đăng ký công khai.'),
            'payment_amount.required_if' => __('Vui lòng nhập số tiền đã thu (hoặc 0) khi đánh dấu đã thu tại quầy.'),
        ];
    }
}
