<?php

namespace App\Services;

use App\Models\LibrarySetting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

class LibrarySettingsService
{
    private const CACHE_KEY_VERSION = 'api:library-settings:version';

    private const CACHE_TTL_SECONDS = 300;

    public function getInt(string $key, int $default = 0): int
    {
        $settings = $this->allAsMap();
        if (! array_key_exists($key, $settings)) {
            return $default;
        }

        $raw = $settings[$key];

        return max(0, (int) $raw);
    }

    public function getString(string $key, string $default = ''): string
    {
        $settings = $this->allAsMap();
        if (! array_key_exists($key, $settings)) {
            return $default;
        }

        return trim((string) $settings[$key]) !== '' ? trim((string) $settings[$key]) : $default;
    }

    public function getDigitalDefaultPdfDownloadPriceVnd(): int
    {
        return $this->getInt(LibrarySetting::KEY_DIGITAL_DEFAULT_PDF_DOWNLOAD_PRICE_VND, 0);
    }

    public function getLateReturnFineMode(): string
    {
        $v = $this->getString(LibrarySetting::KEY_LOAN_LATE_RETURN_FINE_MODE, LibrarySetting::LOAN_LATE_RETURN_FINE_MODE_FIXED_PER_DAY);
        if ($v === LibrarySetting::LOAN_LATE_RETURN_FINE_MODE_PERCENT_BOOK_PRICE_DAILY) {
            return LibrarySetting::LOAN_LATE_RETURN_FINE_MODE_PERCENT_BOOK_PRICE_DAILY;
        }

        return LibrarySetting::LOAN_LATE_RETURN_FINE_MODE_FIXED_PER_DAY;
    }

    public function getLateReturnFinePercentOfBook(): int
    {
        $p = $this->getInt(LibrarySetting::KEY_LOAN_LATE_RETURN_FINE_PERCENT_OF_BOOK, 20);
        if (in_array($p, [20, 30], true)) {
            return $p;
        }

        return 20;
    }

    public function getExternalBorrowFeeVnd(): int
    {
        return $this->getInt(LibrarySetting::KEY_LOAN_EXTERNAL_BORROW_FEE_VND, 0);
    }

    /**
     * @return array<string, mixed>
     */
    public function allAsMap(): array
    {
        $version = (int) Cache::get(self::CACHE_KEY_VERSION, 1);
        $cacheKey = "api:library-settings:v{$version}:map";

        return Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL_SECONDS), function (): array {
            try {
                $rows = LibrarySetting::query()->get(['key', 'type', 'value', 'json_value']);
            } catch (QueryException $e) {
                if ($this->isLibrarySettingsTableMissing($e)) {
                    report($e);

                    return [];
                }

                throw $e;
            }
            $out = [];
            foreach ($rows as $row) {
                $k = (string) $row->key;
                $t = (string) ($row->type ?? 'string');
                if ($t === 'json') {
                    $out[$k] = is_array($row->json_value) ? $row->json_value : [];

                    continue;
                }
                if ($t === 'bool') {
                    $out[$k] = filter_var($row->value, FILTER_VALIDATE_BOOL);

                    continue;
                }
                if ($t === 'int') {
                    $out[$k] = (int) $row->value;

                    continue;
                }
                $out[$k] = $row->value;
            }

            return $out;
        });
    }

    private function isLibrarySettingsTableMissing(QueryException $e): bool
    {
        $msg = $e->getMessage();
        if (! str_contains($msg, 'library_settings')) {
            return false;
        }

        return str_contains($msg, 'no such table')
            || str_contains($msg, "doesn't exist")
            || str_contains($msg, 'Base table or view not found')
            || str_contains($msg, 'Undefined table');
    }

    public function clearCache(): void
    {
        $version = (int) Cache::get(self::CACHE_KEY_VERSION, 1);
        Cache::forever(self::CACHE_KEY_VERSION, $version + 1);
    }
}
