<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\LibrarySettingsPricingRequest;
use App\Http\Requests\LibrarySettingsRequest;
use App\Models\LibrarySetting;
use App\Services\LibrarySettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LibrarySettingsController extends Controller
{
    public function __construct(
        private readonly LibrarySettingsService $settings
    ) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success([
            'digital_default_pdf_download_price_vnd' => $this->settings->getDigitalDefaultPdfDownloadPriceVnd(),
            'loan_late_return_fine_mode' => $this->settings->getLateReturnFineMode(),
            'loan_late_return_fine_percent_of_book' => $this->settings->getLateReturnFinePercentOfBook(),
            'loan_external_borrow_fee_vnd' => $this->settings->getExternalBorrowFeeVnd(),
        ]);
    }

    public function update(LibrarySettingsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $now = now();

        DB::transaction(function () use ($data, $now): void {
            DB::table('library_settings')->upsert(
                [
                    [
                        'key' => LibrarySetting::KEY_DIGITAL_DEFAULT_PDF_DOWNLOAD_PRICE_VND,
                        'type' => 'int',
                        'value' => (string) ((int) $data['digital_default_pdf_download_price_vnd']),
                        'json_value' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                ],
                ['key'],
                ['type', 'value', 'json_value', 'updated_at']
            );
        });

        $this->settings->clearCache();

        return ApiResponse::success(null, __('Đã lưu giá tài liệu số.'));
    }

    /** Phí tài liệu số + phạt trễ hạn + phí mượn bạn đọc ngoài. */
    public function updatePricing(LibrarySettingsPricingRequest $request): JsonResponse
    {
        $data = $request->validated();
        $now = now();

        DB::transaction(function () use ($data, $now): void {
            DB::table('library_settings')->upsert(
                [
                    [
                        'key' => LibrarySetting::KEY_DIGITAL_DEFAULT_PDF_DOWNLOAD_PRICE_VND,
                        'type' => 'int',
                        'value' => (string) ((int) $data['digital_default_pdf_download_price_vnd']),
                        'json_value' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'key' => LibrarySetting::KEY_LOAN_LATE_RETURN_FINE_MODE,
                        'type' => 'string',
                        'value' => (string) $data['loan_late_return_fine_mode'],
                        'json_value' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'key' => LibrarySetting::KEY_LOAN_LATE_RETURN_FINE_PERCENT_OF_BOOK,
                        'type' => 'int',
                        'value' => (string) ((int) $data['loan_late_return_fine_percent_of_book']),
                        'json_value' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'key' => LibrarySetting::KEY_LOAN_EXTERNAL_BORROW_FEE_VND,
                        'type' => 'int',
                        'value' => (string) ((int) $data['loan_external_borrow_fee_vnd']),
                        'json_value' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                ],
                ['key'],
                ['type', 'value', 'json_value', 'updated_at']
            );
        });

        $this->settings->clearCache();

        return ApiResponse::success(null, __('Đã lưu cấu hình phí và phạt.'));
    }
}
