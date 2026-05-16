<?php

namespace App\Services;

use App\Models\DigitalAsset;
use App\Models\DigitalAssetPdfDownloadEntitlement;
use App\Models\DigitalDocumentSubmission;
use App\Models\LibrarySetting;
use Illuminate\Support\Carbon;

class DigitalPaywallService
{
    public function __construct(
        private readonly LibrarySettingsService $librarySettings
    ) {}

    public function resolvePdfDownloadPriceVnd(DigitalAsset $asset): int
    {
        $s = $asset->paywallSetting;
        if ($s !== null && $s->is_paywall_enabled === false) {
            return 0;
        }
        if ($s !== null && $s->pdf_download_price_vnd !== null) {
            return max(0, (int) $s->pdf_download_price_vnd);
        }

        return $this->librarySettings->getDigitalDefaultPdfDownloadPriceVnd();
    }

    public function isPaywallEnabled(DigitalAsset $asset): bool
    {
        return $this->resolvePdfDownloadPriceVnd($asset) > 0;
    }

    /**
     * Số trang xem trước cố định (5). PDF ngắn hơn thì preview gồm hết số trang có.
     */
    public function resolvePreviewMaxPages(DigitalAsset $asset): int
    {
        return LibrarySetting::DEFAULT_DIGITAL_PREVIEW_MAX_PAGES;
    }

    public function userHasPdfDownloadEntitlement(int $userId, int $digitalAssetId, ?Carbon $now = null): bool
    {
        $now = $now ?: now();

        return DigitalAssetPdfDownloadEntitlement::query()
            ->where('user_id', $userId)
            ->where('digital_asset_id', $digitalAssetId)
            ->whereNull('revoked_at')
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->where(function ($q) {
                // Entitlement gắn đơn: chỉ hợp lệ khi đơn đã paid (tránh cấp quyền khi thanh toán chưa xác nhận).
                $q->whereNull('order_id')
                    ->orWhereHas('order', fn ($oq) => $oq->where('status', 'paid'));
            })
            ->exists();
    }

    /**
     * Độc giả đã gửi bản này và thủ thư đã duyệt (đầu mục số gắn approved_book_id).
     */
    public function userIsApprovedSubmitterOfAsset(int $userId, DigitalAsset $asset): bool
    {
        $asset->loadMissing('book.digitalDocumentSubmission');

        $submission = $asset->book?->digitalDocumentSubmission;
        if ($submission === null) {
            return false;
        }

        return $submission->status === DigitalDocumentSubmission::STATUS_APPROVED
            && (int) $submission->submitted_by === $userId;
    }

    /**
     * Quyền tải PDF: tài liệu tự gửi đã duyệt, đã thanh toán, hoặc paywall tắt (miễn phí).
     */
    public function userCanDownloadPdf(int $userId, DigitalAsset $asset, ?Carbon $now = null): bool
    {
        if (! $this->isPaywallEnabled($asset)) {
            return true;
        }

        if ($this->userIsApprovedSubmitterOfAsset($userId, $asset)) {
            return true;
        }

        return $this->userHasPdfDownloadEntitlement($userId, (int) $asset->id, $now);
    }
}
