<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LibraryCard extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'library_cards';

      protected $fillable = [
        'card_number',
        'user_id',
        'issue_date',
        'expiry_date',
        'status',
        'is_active',
        'card_type',
        'note',
        'metadata',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loans(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Loan::class, 'user_id', 'user_id');
    }

    public function canBorrow(int $maxBooks = 5): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expiry_date && $this->expiry_date->isPast()) {
            return false;
        }

        $activeLoansCount = $this->loans()->where('status', 'active')->count();
        if ($activeLoansCount >= $maxBooks) {
            return false;
        }

        if ($this->user && $this->user->fines()->where('status', 'unpaid')->exists()) {
            return false;
        }

        return true;
    }
}
