<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\MasterDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterDataController extends Controller
{
    public function __construct(
        private MasterDataService $masterDataService
    ) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success($this->masterDataService->getPayload());
    }

    public static function clearCache(): void
    {
        MasterDataService::clearCache();
    }
}
