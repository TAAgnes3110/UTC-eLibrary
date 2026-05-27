<?php

namespace App\Http\Requests;

use App\Enums\LoanItemCondition;
use App\Enums\LoanStatus;
use App\Enums\LoanType;
use App\Models\Loan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoanRequest extends FormRequest
{
    /**
     * Dữ liệu tạo/sửa phiếu mượn gửi từ API.
     * Thông báo lỗi dùng tiếng Việt + tên trường dễ đọc (attributes) để form/Postman hiểu ngay.
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
                Rule::enum(LoanType::class),
            ],
            'loan_date' => [
                $isUpdate ? 'sometimes' : 'required',
                'date',
            ],
            'due_date' => [
                $isUpdate ? 'sometimes' : 'required',
                'date',
                'after_or_equal:loan_date',
            ],
            'return_date' => [
                'sometimes',
                'nullable',
                'date',
                'after_or_equal:loan_date',
            ],
            'status' => [
                $isUpdate ? 'sometimes' : 'required',
                Rule::enum(LoanStatus::class),
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
            'library_card_id.required' => 'Vui lòng chọn thẻ thư viện của bạn đọc.',
            'library_card_id.exists' => 'Thẻ thư viện không tồn tại trong hệ thống.',

            'loan_type.required' => 'Vui lòng chọn hình thức mượn (về nhà hoặc tại chỗ).',
            'loan_type.in' => 'Hình thức mượn chỉ được là: mượn về nhà (home) hoặc đọc/mượn tại chỗ (onsite).',

            'loan_date.required' => 'Vui lòng nhập ngày mượn.',
            'loan_date.date' => 'Ngày mượn không đúng định dạng ngày.',

            'due_date.required' => 'Vui lòng nhập ngày hẹn trả.',
            'due_date.date' => 'Ngày hẹn trả không đúng định dạng ngày.',
            'due_date.after_or_equal' => 'Ngày hẹn trả không được trước ngày mượn.',

            'return_date.date' => 'Ngày trả không đúng định dạng ngày.',
            'return_date.after_or_equal' => 'Ngày trả không được trước ngày mượn.',

            'status.required' => 'Vui lòng chọn trạng thái phiếu.',
            'status.in' => 'Trạng thái phiếu không hợp lệ (đang mượn / đã trả / quá hạn).',

            'book_ids.required' => 'Phiếu mượn cần ít nhất một đầu sách.',
            'book_ids.array' => 'Danh sách sách phải là một mảng.',
            'book_ids.min' => 'Phiếu mượn cần ít nhất một đầu sách.',
            'book_ids.*.exists' => 'Có mã sách không tồn tại trong hệ thống.',

            'quantity.required' => 'Vui lòng nhập số lượng mượn cho từng đầu sách.',
            'quantity.array' => 'Số lượng mượn phải kèm theo dạng danh sách (theo từng sách).',
            'quantity.*.integer' => 'Số lượng mỗi đầu sách phải là số nguyên.',
            'quantity.*.min' => 'Số lượng mượn mỗi đầu sách phải ít nhất là 1.',

            'condition_on_loan.required' => 'Vui lòng chọn tình trạng sách khi mượn cho từng dòng.',
            'condition_on_loan.array' => 'Tình trạng khi mượn phải là danh sách (khớp với từng sách).',
            'condition_on_loan.*.enum' => 'Tình trạng khi mượn chỉ được: tốt (tot), hỏng (hong), mất (mat).',

            'condition_on_return.enum' => 'Tình trạng khi trả chỉ được: tốt (tot), hỏng (hong), mất (mat).',

            'fine_amount.numeric' => 'Tiền phạt phải là số.',
            'fine_amount.min' => 'Tiền phạt không được âm.',

            'returns.array' => 'Dữ liệu trả theo từng dòng phải là danh sách.',
            'returns.*.condition_on_return.enum' => 'Tình trạng khi trả (từng dòng) chỉ được: tot, hong, mat.',
            'returns.*.fine_amount.numeric' => 'Tiền phạt từng dòng phải là số.',
            'returns.*.fine_amount.min' => 'Tiền phạt từng dòng không được âm.',

            'notes.max' => 'Ghi chú không được vượt quá :max ký tự.',
        ];
    }

    /**
     * Tên trường hiển thị trong câu lỗi mặc định của Laravel (thay cho tên cột tiếng Anh).
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'library_card_id' => 'thẻ thư viện',
            'loan_type' => 'hình thức mượn',
            'loan_date' => 'ngày mượn',
            'due_date' => 'ngày hẹn trả',
            'return_date' => 'ngày trả',
            'status' => 'trạng thái phiếu',
            'book_ids' => 'danh sách sách',
            'book_ids.*' => 'mã sách',
            'quantity' => 'số lượng mượn',
            'quantity.*' => 'số lượng từng đầu sách',
            'condition_on_loan' => 'tình trạng khi mượn',
            'condition_on_loan.*' => 'tình trạng sách khi mượn',
            'condition_on_return' => 'tình trạng khi trả',
            'fine_amount' => 'tiền phạt',
            'returns' => 'chi tiết trả sách',
            'returns.*.condition_on_return' => 'tình trạng khi trả (từng dòng)',
            'returns.*.fine_amount' => 'tiền phạt (từng dòng)',
            'notes' => 'ghi chú',
        ];
    }
}
