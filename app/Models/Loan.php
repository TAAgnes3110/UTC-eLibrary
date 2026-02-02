<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Loan extends BaseModel
{
  public static string $tableName = 'loans';
  protected $table = 'loans';

  protected $fillable = [
    'user_id',
    'reader_id',
    'book_copy_id',
    'librarian_id',
    'loan_date',
    'due_date',
    'return_date',
    'status',
    'condition_on_loan',
    'condition_on_return',
    'overdue_days',
    'overdue_fine',
    'renewal_count',
    'max_renewals',
    'last_renewal_date',
    'notes',
    'params',
  ];

  protected $casts = [
    'loan_date' => 'date',
    'due_date' => 'date',
    'return_date' => 'date',
    'last_renewal_date' => 'date',
    'overdue_fine' => 'decimal:2',
    'params' => 'array',
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function reader(): BelongsTo
  {
    return $this->belongsTo(Reader::class);
  }

  public function bookCopy(): BelongsTo
  {
    return $this->belongsTo(BookCopy::class);
  }

  public function librarian(): BelongsTo
  {
    return $this->belongsTo(User::class, 'librarian_id');
  }

  public function history(): HasMany
  {
    return $this->hasMany(LoanHistory::class);
  }

  public function fine(): HasOne
  {
    return $this->hasOne(Fine::class);
  }

  public function isOverdue(): bool
  {
    if ($this->status === 'returned') {
      return false;
    }
    return now()->gt($this->due_date);
  }

  public function canRenew(): bool
  {
    if ($this->status !== 'active') {
      return false;
    }

    if ($this->renewal_count >= $this->max_renewals) {
      return false;
    }

    if ($this->isOverdue()) {
      return false;
    }

    return true;
  }
}
