<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Helpers\DeployHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\DigitalAssetResource;
use App\Models\Book;
use App\Models\DigitalAsset;
use App\Models\User;
use App\Services\DigitalAssetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DigitalAssetController extends Controller
{
    public function __construct(
        private DigitalAssetService $digitalAssetService
    ) {}

    public function store(Request $request, Book $book): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:'.DeployHelper::maxDigitalPdfUploadKilobytes()],
            'is_primary' => ['sometimes', 'boolean'],
            'visibility' => ['sometimes', 'string', 'in:public,internal,restricted'],
            'embargo_until' => ['sometimes', 'nullable', 'date'],
        ]);

        $attrs = array_intersect_key($validated, array_flip(['is_primary', 'visibility', 'embargo_until']));
        $file = $request->file('file');
        if (! $file) {
            return ApiResponse::error(__('Vui lòng chọn file PDF.'), 422);
        }

        $asset = $this->digitalAssetService->store($book, $file, $attrs);

        return ApiResponse::success(new DigitalAssetResource($asset), __('messages.success_create'), 201);
    }

    public function destroy(Book $book, DigitalAsset $digital_asset): JsonResponse
    {
        $this->digitalAssetService->destroy($book, $digital_asset);

        return ApiResponse::success(null, __('messages.success_delete'));
    }

    /**
     * Thủ thư tải PDF gốc (disk private/local) — không qua paywall độc giả.
     */
    public function download(Request $request, Book $book, DigitalAsset $digital_asset): StreamedResponse
    {
        $this->assertAuthenticatedForDownload($request);

        if ((int) $digital_asset->book_id !== (int) $book->id) {
            abort(404);
        }

        $safeFilename = $this->digitalAssetService->buildPdfDownloadFilename($digital_asset, $book);

        return $this->digitalAssetService->streamPdfDownloadResponse($digital_asset, $safeFilename);
    }

    private function assertAuthenticatedForDownload(Request $request): void
    {
        global $currentPerson;

        $user = $currentPerson instanceof User
            ? $currentPerson
            : ($request->user() ?? Auth::guard('web')->user());

        if (! $user) {
            abort(401, __('Bạn cần đăng nhập để tiếp tục.'));
        }
    }
}
