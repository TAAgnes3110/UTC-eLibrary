<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoanPolicies;
use App\Http\Resources\LoanPolicyResource;
use App\Models\LoanPolicy;
use App\Services\LoanPoliciesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoanPoliciesController extends Controller
{
    public function __construct(
        private LoanPoliciesService $loanPoliciesService
    ) {}

    /**
     * Danh sách quy định mượn (cấu hình theo đối tượng).
     */
    public function index(Request $request): JsonResponse
    {
        $items = LoanPolicy::query()
            ->orderByRaw("CASE COALESCE(user_type, '') WHEN 'STUDENT' THEN 1 WHEN 'TEACHER' THEN 2 WHEN 'MEMBER' THEN 3 ELSE 9 END")
            ->orderBy('id')
            ->get();

        return ApiResponse::success(LoanPolicyResource::collection($items));
    }

    public function store(LoanPolicies $request): JsonResponse
    {
        $loanPolicy = $this->loanPoliciesService->create($request->validated());

        return ApiResponse::success(new LoanPolicyResource($loanPolicy), __('Thêm chính sách mượn thành công.'), 201);
    }

    public function update(LoanPolicies $request, LoanPolicy $loanPolicy): JsonResponse
    {
        $loanPolicy = $this->loanPoliciesService->update($loanPolicy, $request->validated());

        return ApiResponse::success(new LoanPolicyResource($loanPolicy), __('Cập nhật chính sách mượn thành công.'));
    }
}
