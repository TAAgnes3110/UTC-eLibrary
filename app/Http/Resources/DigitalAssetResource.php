<?php

namespace App\Http\Resources;

use App\Services\DigitalAssetPreviewService;
use App\Services\DigitalPaywallService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DigitalAssetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DigitalPaywallService $paywall */
        $paywall = app(DigitalPaywallService::class);
        /** @var DigitalAssetPreviewService $preview */
        $preview = app(DigitalAssetPreviewService::class);
        $pdfPriceVnd = $paywall->resolvePdfDownloadPriceVnd($this->resource);
        $isPaywallEnabled = $paywall->isPaywallEnabled($this->resource);
        $previewAvailable = $preview->isPreviewAvailableForReader($this->resource);

        $userCanDownloadPdf = false;
        $isOwnApprovedSubmission = false;
        if ($user = $request->user()) {
            $userId = (int) $user->id;
            $isOwnApprovedSubmission = $paywall->userIsApprovedSubmitterOfAsset($userId, $this->resource);
            $userCanDownloadPdf = $paywall->userCanDownloadPdf($userId, $this->resource);
        }

        return [
            'id' => $this->id,
            'book_id' => $this->book_id,
            'version' => $this->version,
            'is_primary' => $this->is_primary,
            'storage_disk' => $this->storage_disk,
            'path' => $this->path,
            'download_url' => route('reader.catalog.digital-download-pdf', [
                'book' => $this->book_id,
                'digital_asset' => $this->id,
            ], false),
            'admin_download_url' => route('admin.books.digital-assets.download', [
                'book' => $this->book_id,
                'digital_asset' => $this->id,
            ], false),
            'preview_available' => $previewAvailable,
            'preview_url' => $previewAvailable ? route('reader.catalog.digital-preview', [
                'book' => $this->book_id,
                'digital_asset' => $this->id,
            ], false) : null,
            'original_name' => $this->original_name,
            'mime' => $this->mime,
            'byte_size' => $this->byte_size,
            'checksum_sha256' => $this->checksum_sha256,
            'visibility' => $this->visibility,
            'embargo_until' => $this->embargo_until?->toDateString(),
            'params' => $this->params ?? [],
            'paywall' => [
                'is_enabled' => $isPaywallEnabled,
                'pdf_download_price_vnd' => $pdfPriceVnd,
                'currency' => 'VND',
            ],
            'user_can_download_pdf' => $userCanDownloadPdf,
            'is_own_approved_submission' => $isOwnApprovedSubmission,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
