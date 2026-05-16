<?php

namespace App\Models;

use App\Models\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Builder;

class LibrarySetting extends BaseModel
{
    use HasAuditFields;

    protected static bool $persistParamsToDatabase = false;

    public const KEY_DIGITAL_DEFAULT_PDF_DOWNLOAD_PRICE_VND = 'digital.default_pdf_download_price_vnd';

    public const DEFAULT_DIGITAL_PREVIEW_MAX_PAGES = 5;

    public const KEY_LOAN_LATE_RETURN_FINE_MODE = 'loan.late_return_fine_mode';

    public const KEY_LOAN_LATE_RETURN_FINE_PERCENT_OF_BOOK = 'loan.late_return_fine_percent_of_book';

    public const KEY_LOAN_EXTERNAL_BORROW_FEE_VND = 'loan.external_borrow_fee_vnd';

    public const LOAN_LATE_RETURN_FINE_MODE_FIXED_PER_DAY = 'fixed_per_day';

    public const LOAN_LATE_RETURN_FINE_MODE_PERCENT_BOOK_PRICE_DAILY = 'percent_book_price_daily';

    protected $fillable = [
        'key',
        'type',
        'value',
        'json_value',
    ];

    protected $casts = [
        'json_value' => 'array',
    ];

    /**
     * @param  Builder<static>  $query
     */
    public function scopeKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }
}
