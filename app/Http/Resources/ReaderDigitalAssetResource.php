<?php

namespace App\Http\Resources;

use App\Services\DigitalAssetPreviewService;
use App\Services\DigitalPaywallService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Tài liệu số trên trang tra cứu độc giả — payload nhẹ, không gọi Storage::exists. */
class ReaderDigitalAssetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DigitalPaywallService $paywall */
        $paywall = app(DigitalPaywallService::class);
        $setting = $this->relationLoaded('paywallSetting') ? $this->paywallSetting : null;

        if ($setting !== null) {
            $pdfPriceVnd = $setting->is_paywall_enabled === false
                ? 0
                : max(0, (int) ($setting->pdf_download_price_vnd ?? $paywall->resolvePdfDownloadPriceVnd($this->resource)));
            $isPaywallEnabled = $setting->is_paywall_enabled !== false && $pdfPriceVnd > 0;
        } else {
            $pdfPriceVnd = $paywall->resolvePdfDownloadPriceVnd($this->resource);
            $isPaywallEnabled = $pdfPriceVnd > 0;
        }

        /** @var DigitalAssetPreviewService $previewService */
        $previewService = app(DigitalAssetPreviewService::class);
        $previewAvailable = $previewService->isPreviewAvailableForReader($this->resource);

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
            'is_primary' => $this->is_primary,
            'preview_available' => $previewAvailable,
            'preview_url' => $previewAvailable ? route('reader.catalog.digital-preview', [
                'book' => $this->book_id,
                'digital_asset' => $this->id,
            ], false) : null,
            'original_name' => $this->original_name,
            'byte_size' => $this->byte_size,
            'paywall' => [
                'is_enabled' => $isPaywallEnabled,
                'pdf_download_price_vnd' => $pdfPriceVnd,
                'currency' => 'VND',
            ],
            'user_can_download_pdf' => $userCanDownloadPdf,
            'is_own_approved_submission' => $isOwnApprovedSubmission,
        ];
    }
}
