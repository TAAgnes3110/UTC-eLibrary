<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLibraryCardUpdateRequest;
use App\Http\Resources\LibraryCardResource;
use App\Models\LibraryCard;
use App\Services\LibraryCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LibraryCardController extends Controller
{
    public function __construct(
        private LibraryCardService $libraryCardService
    ) {}

    /**
     * GET /api/v1/library-cards — danh sách (admin/thủ thư).
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'workflow_status' => ['sometimes', 'nullable', 'string', 'max:32'],
            'keyword' => ['sometimes', 'nullable', 'string', 'max:200'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $items = $this->libraryCardService->indexForAdmin(
            $request->input('workflow_status'),
            $request->input('keyword'),
            (int) $request->input('per_page', 30),
        );

        return ApiResponse::success(LibraryCardResource::collection($items));
    }

    /**
     * GET /api/v1/library-cards/{library_card}
     */
    public function show(LibraryCard $libraryCard): JsonResponse
    {
        $card = $this->libraryCardService->getForAdminDetail($libraryCard);

        return ApiResponse::success(new LibraryCardResource($card));
    }

    /**
     * PATCH /api/v1/library-cards/{library_card}
     */
    public function update(AdminLibraryCardUpdateRequest $request, LibraryCard $libraryCard): JsonResponse
    {
        $card = $this->libraryCardService->updateCard($libraryCard, $request->validated());

        return ApiResponse::success(new LibraryCardResource($card), __('messages.success_update'));
    }
}
