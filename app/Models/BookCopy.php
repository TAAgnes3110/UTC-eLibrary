<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookCopy extends BaseModel
{
  use SoftDeletes;

  protected $fillable = [
    'book_id',
    'barcode',
    'call_number',
    'condition',
    'status',
    'location',
    'notes',
    'params',
  ];

  protected $casts = [
    'params' => 'array',
  ];

  public function book(): BelongsTo
  {
    return $this->belongsTo(Book::class);
  }

  public function loans(): HasMany
  {
    return $this->hasMany(Loan::class);
  }

  public function activeLoan(): ?Loan
  {
    return $this->loans()->where('status', 'active')->first();
  }
}
