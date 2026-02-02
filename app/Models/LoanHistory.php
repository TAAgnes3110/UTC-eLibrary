<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanHistory extends BaseModel
{


  public $timestamps = false;

  protected $fillable = [
    'loan_id',
    'action',
    'performed_by',
    'notes',
    'metadata',
    'performed_at',
  ];

  protected $casts = [
    'metadata' => 'array',
    'performed_at' => 'datetime',
  ];

  /**
   * Get the loan that owns the history.
   */
  public function loan(): BelongsTo
  {
    return $this->belongsTo(Loan::class);
  }

  /**
   * Get the user who performed the action.
   */
  public function performer(): BelongsTo
  {
    return $this->belongsTo(User::class, 'performed_by');
  }

  /**
   * Create a new loan history entry.
   */
  public static function log(
    int $loanId,
    string $action,
    ?int $performedBy = null,
    ?string $notes = null,
    ?array $metadata = null
  ): self {
    return self::create([
      'loan_id' => $loanId,
      'action' => $action,
      'performed_by' => $performedBy ?? auth()->id(),
      'notes' => $notes,
      'metadata' => $metadata,
      'performed_at' => now(),
    ]);
  }
}
