<?php

namespace App\Http\Requests;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Validation\Rule;

class UserRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $existing = $this->routeUser();
        $incomingRole = $this->input('user_type') ?? $this->input('role');
        if ($incomingRole !== null && $incomingRole !== '') {
            $resolvedType = (string) $incomingRole;
        } elseif ($existing) {
            $ut = $existing->user_type;
            $resolvedType = $ut instanceof \BackedEnum ? $ut->value : (string) $ut;
        } else {
            $resolvedType = RoleType::MEMBER->value;
        }

        $this->merge([
            'user_type' => $resolvedType,
        ]);

        $type = RoleType::tryFrom($resolvedType);

        $stripAll = $type === null || in_array($type, [
            RoleType::MEMBER,
            RoleType::GUEST,
            RoleType::SUPER_ADMIN,
            RoleType::ADMIN,
            RoleType::LIBRARIAN,
        ], true);

        if ($stripAll) {
            $this->merge([
                'faculty_id' => null,
                'department_id' => null,
                'period_id' => null,
                'class_code' => null,
                'cohort' => null,
            ]);
        } elseif ($type === RoleType::TEACHER) {
            $this->merge([
                'period_id' => null,
                'class_code' => null,
                'cohort' => null,
            ]);
        }
    }

    private function routeUser(): ?User
    {
        $user = $this->route('user');
        if ($user instanceof User) {
            return $user;
        }
        if (is_numeric($user)) {
            return User::query()->find((int) $user);
        }

        return null;
    }

    public function rules(): array
    {
        $id = $this->route('user');
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $isCreate = ! $isUpdate;
        $userType = (string) $this->input('user_type', '');
        $isStudent = $userType === RoleType::STUDENT->value;
        $isTeacher = $userType === RoleType::TEACHER->value;

        return [
            'code' => $isUpdate
                ? ['sometimes', 'nullable', 'string', 'max:255']
                : [
                    'required',
                    'string',
                    'regex:/^\d{9,12}$/',
                    Rule::unique('users', 'code')->ignore($id),
                ],
            'name' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255',
            ],
            'email' => [
                $isUpdate ? 'sometimes' : 'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($id),
            ],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                'regex:/^0[0-9]{9,10}$/',
                Rule::unique('users', 'phone')->ignore($id),
            ],
            'password' => [
                $isUpdate ? 'sometimes' : 'required',
                'nullable',
                'string',
                'min:6',
                'confirmed',
            ],
            'user_type' => [
                $isUpdate ? 'sometimes' : 'required',
                Rule::in(array_values(collect(RoleType::cases())
                    ->reject(fn ($it) => $it === RoleType::GUEST)
                    ->pluck('value')
                    ->all())),
            ],
            'gender' => ['sometimes', 'nullable', 'string', 'in:male,female,other'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before:today'],
            'address' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'avatar' => ['sometimes', 'nullable', 'string', 'max:500'],
            'faculty_id' => $isCreate && ($isStudent || $isTeacher)
                ? ['required', 'integer', 'min:1', 'exists:faculties,id']
                : ['sometimes', 'nullable', 'integer', 'min:1', 'exists:faculties,id'],
            'department_id' => ['sometimes', 'nullable', 'integer', 'min:1', 'exists:departments,id'],
            'cohort' => ['sometimes', 'nullable', 'string', 'max:20'],
            'period_id' => $isCreate && $isStudent
                ? ['required', 'integer', 'min:1', 'exists:periods,id']
                : ['sometimes', 'nullable', 'integer', 'min:1', 'exists:periods,id'],
            'class_code' => ['sometimes', 'nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => __('Số CCCD/CMND không được để trống'),
            'code.unique' => __('Số định danh đã tồn tại trong hệ thống.'),
            'code.regex' => __('Số CCCD/CMND phải gồm 9–12 chữ số.'),
            'name.required' => __('Tên không được để trống'),
            'email.required' => __('Email không được để trống'),
            'email.unique' => __('Email đã tồn tại trong hệ thống.'),
            'phone.unique' => __('Số điện thoại đã tồn tại trong hệ thống.'),
            'phone.regex' => __('Số điện thoại không đúng định dạng (bắt đầu bằng 0, 10–11 số).'),
            'password.required' => __('Mật khẩu không được để trống'),
            'password.min' => __('Mật khẩu tối thiểu 6 ký tự'),
            'password.confirmed' => __('Xác nhận mật khẩu không khớp'),
        ];
    }
}
