<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fine extends BaseModel
{
  protected $fillable = [
    'loan_id',
    'user_id',
    'amount',
    'reason',
    'description',
    'status',
    'paid_date',
    'payment_method',
    'processed_by',
    'notes',
    'params',
  ];

  protected $casts = [
    'amount' => 'decimal:2',
    'paid_date' => 'date',
    'params' => 'array',
  ];

  public function loan(): BelongsTo
  {
    return $this->belongsTo(Loan::class);
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function processor(): BelongsTo
  {
    return $this->belongsTo(User::class, 'processed_by');
  }
}
