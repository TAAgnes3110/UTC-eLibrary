<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\LibraryCardResource;
use App\Services\LibraryCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeLibraryCardController extends Controller
{
    public function __construct(
        private LibraryCardService $libraryCardService
    ) {}

    /**
     * GET /api/v1/me/library-card — thẻ thư viện của user đăng nhập.
     */
    public function show(Request $request): JsonResponse
    {
        $card = $this->libraryCardService->getCardForUser($request->user());

        return ApiResponse::success([
            'library_card' => $card ? new LibraryCardResource($card) : null,
        ]);
    }
}
