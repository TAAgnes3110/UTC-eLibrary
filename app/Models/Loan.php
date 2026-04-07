<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Loan extends BaseModel
{
    public const STATUS_BORROWING = 'dang_muon';

    public const STATUS_RETURNED = 'da_tra';

    public const STATUS_OVERDUE = 'qua_han';

    protected $fillable = [
        'library_card_id',
        'book_copy_id',
        'user_id',
        'loan_date',
        'due_date',
        'return_date',
        'status',
        'notes',
        'params',
    ];

    protected $casts = [
        'loan_date' => 'date',
        'due_date' => 'date',
        'return_date' => 'date',
        'params' => 'array',
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

    public function librarian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function bookCopy(): BelongsTo
    {
        return $this->belongsTo(BookCopy::class);
    }

    public function libraryCard(): BelongsTo
    {
        return $this->belongsTo(LibraryCard::class);
    }

    /** Chi tiết đầu sách trên phiếu (sach + số lượng + tình trạng + phạt dòng). */
    public function items(): HasMany
    {
        return $this->hasMany(LoanItem::class);
    }
}
