<?php

namespace App\Models;

use App\Enums\LoanStatus;
use App\Enums\LoanType;
use App\Models\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Loan extends BaseModel
{
    use HasAuditFields;

    protected static bool $persistParamsToDatabase = false;

    protected $fillable = [
        'loan_code',
        'library_card_id',
        'loan_type',
        'loan_date',
        'due_date',
        'return_date',
        'status',
        'deleted',
    ];

    protected $casts = [
        'loan_date' => 'date',
        'due_date' => 'date',
        'return_date' => 'date',
        'loan_type' => LoanType::class,
        'status' => LoanStatus::class,
        'deleted' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('notDeleted', function (Builder $builder): void {
            $builder->where($builder->getModel()->getTable().'.deleted', false);
        });
    }

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
