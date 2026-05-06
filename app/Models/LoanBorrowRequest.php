<?php

namespace App\Models;

use App\Models\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanBorrowRequest extends BaseModel
{
    use HasAuditFields;

    protected static bool $persistParamsToDatabase = false;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
        ];
    }

    protected $fillable = [
        'request_code',
        'library_card_id',
        'requested_by',
        'loan_type',
        'requested_loan_date',
        'requested_due_date',
        'status',
        'request_note',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'approved_loan_id',
    ];

    protected $casts = [
        'requested_loan_date' => 'date',
        'requested_due_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function libraryCard(): BelongsTo
    {
        return $this->belongsTo(LibraryCard::class, 'library_card_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedLoan(): BelongsTo
    {
        return $this->belongsTo(Loan::class, 'approved_loan_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LoanBorrowRequestItem::class, 'borrow_request_id');
    }
}
