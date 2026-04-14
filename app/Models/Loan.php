<?php

namespace App\Models;

use App\Models\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends BaseModel
{
    use HasAuditFields, SoftDeletes;

    protected static bool $persistParamsToDatabase = false;

    public const TYPE_HOME = 'home';

    public const TYPE_ONSITE = 'onsite';

    public const STATUS_BORROWED = 'da_muon';

    public const STATUS_RETURNED = 'da_tra';

    public const STATUS_OVERDUE = 'qua_han';

    protected $fillable = [
        'loan_code',
        'library_card_id',
        'loan_type',
        'loan_date',
        'due_date',
        'return_date',
        'status',
    ];

    protected $casts = [
        'loan_date' => 'date',
        'due_date' => 'date',
        'return_date' => 'date',
    ];

    /**
     * Bạn đọc có tài khoản: loan → library_card → user.
     */
    public function reader(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            LibraryCard::class,
            'id',
            'id',
            'library_card_id',
            'user_id'
        );
    }

    public function libraryCard(): BelongsTo
    {
        return $this->belongsTo(LibraryCard::class);
    }

    /** Chi tiết đầu sách trên phiếu. */
    public function items(): HasMany
    {
        return $this->hasMany(LoanItem::class);
    }

    public function renewalRequests(): HasMany
    {
        return $this->hasMany(LoanRenewalRequest::class);
    }
}
