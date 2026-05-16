<?php

namespace App\Http\Controllers\Api\Me;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\DigitalPurchaseCartBulkDeleteRequest;
use App\Http\Requests\DigitalPurchaseCartItemStoreRequest;
use App\Http\Resources\DigitalPurchaseCartItemResource;
use App\Models\DigitalAsset;
use App\Services\DigitalPurchaseCartService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DigitalPurchaseCartController extends Controller
{
    public function __construct(
        private readonly DigitalPurchaseCartService $cart
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return ApiResponse::error(__('Vui lòng đăng nhập.'), 401);
        }

        $items = $this->cart->listDigitalItemsForUser($user);

        return ApiResponse::success([
            'items' => DigitalPurchaseCartItemResource::collection($items)->resolve(),
        ]);
    }

    public function count(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return ApiResponse::error(__('Vui lòng đăng nhập.'), 401);
        }

        return ApiResponse::success([
            'count' => $this->cart->countItemsForUser($user),
        ]);
    }

    public function store(DigitalPurchaseCartItemStoreRequest $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return ApiResponse::error(__('Vui lòng đăng nhập.'), 401);
        }

        $data = $request->validated();
        $meta = [
            'book_id' => $data['book_id'] ?? null,
            'book_title' => $data['book_title'] ?? null,
            'file_name' => $data['file_name'] ?? null,
            'cover_image' => $data['cover_image'] ?? null,
        ];

        try {
            $item = $this->cart->addDigitalItem($user, (int) $data['digital_asset_id'], $meta);
        } catch (ModelNotFoundException) {
            return ApiResponse::error(__('Không tìm thấy tài liệu.'), 404);
        }

        return ApiResponse::success([
            'item' => (new DigitalPurchaseCartItemResource($item))->resolve(),
        ], __('Đã thêm vào giỏ thanh toán.'), 201);
    }

    public function destroy(Request $request, DigitalAsset $digital_asset): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return ApiResponse::error(__('Vui lòng đăng nhập.'), 401);
        }

        $this->cart->removeDigitalItem($user, (int) $digital_asset->id);

        return ApiResponse::success(null, __('Đã xóa khỏi giỏ thanh toán.'));
    }

    public function bulkDestroy(DigitalPurchaseCartBulkDeleteRequest $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return ApiResponse::error(__('Vui lòng đăng nhập.'), 401);
        }

        $ids = $request->validated()['digital_asset_ids'];
        $this->cart->removeDigitalItems($user, $ids);

        return ApiResponse::success(null, __('Đã xóa các mục đã chọn khỏi giỏ.'));
    }
}
