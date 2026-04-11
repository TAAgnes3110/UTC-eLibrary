<?php

namespace App\Http\Requests;

use App\Enums\LoanItemCondition;
use App\Models\Loan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoanRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        if ($isUpdate && ! $this->filled('loan_date')) {
            $routeLoan = $this->route('loan');
            if ($routeLoan instanceof Loan && $routeLoan->loan_date !== null) {
                $this->merge(['loan_date' => $routeLoan->loan_date->format('Y-m-d')]);
            }
        }

        return [
            'library_card_id' => [
                $isUpdate ? 'sometimes' : 'required',
                'exists:library_cards,id',
            ],
            'loan_type' => [
                $isUpdate ? 'sometimes' : 'required',
                Rule::in([Loan::TYPE_HOME, Loan::TYPE_ONSITE]),
            ],
            'loan_date' => [
                $isUpdate ? 'sometimes' : 'required',
                'date',
            ],
            'due_date' => [
                $isUpdate ? 'sometimes' : 'required',
                'date',
                'after:loan_date',
            ],
            'return_date' => [
                'sometimes',
                'nullable',
                'date',
                'after_or_equal:loan_date',
            ],
            'status' => [
                $isUpdate ? 'sometimes' : 'required',
                Rule::in([
                    Loan::STATUS_BORROWED,
                    Loan::STATUS_RETURNED,
                    Loan::STATUS_OVERDUE,
                ]),
            ],
            'book_ids' => [
                $isUpdate ? 'sometimes' : 'required',
                'array',
                'min:1',
            ],
            'book_ids.*' => [
                'exists:books,id',
            ],
            'quantity' => [
                $isUpdate ? 'sometimes' : 'required',
                'array',
            ],
            'quantity.*' => [
                'integer',
                'min:1',
            ],
            'condition_on_loan' => [
                $isUpdate ? 'sometimes' : 'required',
                'array',
            ],
            'condition_on_loan.*' => [
                Rule::enum(LoanItemCondition::class),
            ],
            'condition_on_return' => [
                'sometimes',
                'nullable',
                Rule::enum(LoanItemCondition::class),
            ],
            'fine_amount' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],
            'returns' => [
                'sometimes',
                'array',
            ],
            'returns.*.condition_on_return' => [
                'sometimes',
                'nullable',
                Rule::enum(LoanItemCondition::class),
            ],
            'returns.*.fine_amount' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'due_date.after' => 'Ngày hẹn trả phải lớn hơn ngày mượn.',
            'return_date.after_or_equal' => 'Ngày trả không được nhỏ hơn ngày mượn.',
            'condition_on_loan.*.enum' => 'Tình trạng khi mượn chỉ nhận: tot, hong, mat.',
            'condition_on_return.enum' => 'Tình trạng khi trả chỉ nhận: tot, hong, mat.',
            'fine_amount.min' => 'Tiền phạt không được âm.',
            'returns.*.condition_on_return.enum' => 'Tình trạng khi trả (theo dòng) chỉ nhận: tot, hong, mat.',
            'returns.*.fine_amount.min' => 'Tiền phạt theo dòng không được âm.',
        ];
    }
}
