<?php

namespace Tests\Feature\Backend;

use App\Models\LibrarySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LibrarySettingsPricingApiTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    #[Test]
    public function admin_can_put_library_settings_pricing_and_rows_are_upserted(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $payload = [
            'digital_default_pdf_download_price_vnd' => 15000,
            'loan_late_return_fine_mode' => LibrarySetting::LOAN_LATE_RETURN_FINE_MODE_PERCENT_BOOK_PRICE_DAILY,
            'loan_late_return_fine_percent_of_book' => 30,
            'loan_external_borrow_fee_vnd' => 5000,
        ];

        $response = $this->putJson('/api/v1/library-settings/pricing', $payload, $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonPath('status', 'success');

        $this->assertSame('15000', DB::table('library_settings')->where('key', LibrarySetting::KEY_DIGITAL_DEFAULT_PDF_DOWNLOAD_PRICE_VND)->value('value'));
        $this->assertSame(
            LibrarySetting::LOAN_LATE_RETURN_FINE_MODE_PERCENT_BOOK_PRICE_DAILY,
            DB::table('library_settings')->where('key', LibrarySetting::KEY_LOAN_LATE_RETURN_FINE_MODE)->value('value')
        );
        $this->assertSame('30', DB::table('library_settings')->where('key', LibrarySetting::KEY_LOAN_LATE_RETURN_FINE_PERCENT_OF_BOOK)->value('value'));
        $this->assertSame('5000', DB::table('library_settings')->where('key', LibrarySetting::KEY_LOAN_EXTERNAL_BORROW_FEE_VND)->value('value'));
    }

    #[Test]
    public function put_library_settings_pricing_returns_422_when_percent_not_20_or_30(): void
    {
        [, $token] = $this->createAdminUserAndToken();

        $response = $this->putJson('/api/v1/library-settings/pricing', [
            'digital_default_pdf_download_price_vnd' => 0,
            'loan_late_return_fine_mode' => LibrarySetting::LOAN_LATE_RETURN_FINE_MODE_PERCENT_BOOK_PRICE_DAILY,
            'loan_late_return_fine_percent_of_book' => 25,
            'loan_external_borrow_fee_vnd' => 0,
        ], $this->apiTokenHeaders($token));

        $response->assertStatus(422);
    }
}
