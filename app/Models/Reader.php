<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reader extends BaseModel
{
  public static string $tableName = 'readers';
  protected $table = 'readers';
  public $primaryKey = 'id';

  protected $fillable = [
    'user_id',
    'reader_code',
    'full_name',
    'phone',
    'address',
    'birth_date',
    'student_code',
    'employee_code',
    'faculty_id',
    'department_id',
    'reader_type',
    'card_number',
    'card_issued_date',
    'card_expiry_date',
    'card_status',
    'max_books',
    'max_days',
    'is_active',
    'notes',
    'params',
  ];

  protected $casts = [
    'birth_date' => 'date',
    'card_issued_date' => 'date',
    'card_expiry_date' => 'date',
    'is_active' => 'boolean',
    'params' => 'object',
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function faculty(): BelongsTo
  {
    return $this->belongsTo(Faculty::class);
  }

  public function department(): BelongsTo
  {
    return $this->belongsTo(Department::class);
  }

  public function loans(): HasMany
  {
    return $this->hasMany(Loan::class);
  }

  public function activeLoans(): HasMany
  {
    return $this->loans()->where('status', 'active');
  }

  public function fines(): HasMany
  {
    return $this->hasMany(Fine::class);
  }

  public function unpaidFines(): HasMany
  {
    return $this->fines()->where('status', 'unpaid');
  }

  public function canBorrow(): bool
  {
    if (!$this->is_active) {
      return false;
    }

    if ($this->card_status !== 'active') {
      return false;
    }

    if ($this->card_expiry_date && $this->card_expiry_date->isPast()) {
      return false;
    }

    $activeLoansCount = $this->activeLoans()->count();
    if ($activeLoansCount >= $this->max_books) {
      return false;
    }

    if ($this->unpaidFines()->exists()) {
      return false;
    }

    return true;
  }

  public function renewCard(int $months = 12): void
  {
    $this->card_expiry_date = now()->addMonths($months);
    $this->card_status = 'active';
    $this->save();
  }

  public function suspendCard(string $reason = null): void
  {
    $this->card_status = 'suspended';
    $this->notes = $reason ? ($this->notes . "\nLý do khóa: " . $reason) : $this->notes;
    $this->save();
  }
}
