<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class LoanHistory extends BaseModel
{
    public $timestamps = false;

    protected $table = 'loan_histories';

    protected $fillable = [
        'loan_id',
        'action',
        'performed_by',
        'notes',
        'metadata',
        'params',
        'performed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'params' => 'array',
        'performed_at' => 'datetime',
    ];

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
     * Ghi lịch sử phiếu mượn (created, renewed, returned, overdue, lost, damaged, cancelled).
     */
    public static function log(
        int $loanId,
        string $action,
        ?int $performedBy = null,
        ?string $notes = null,
        ?array $metadata = null
    ): self {
        $userId = $performedBy ?? Auth::id();
        return self::create([
            'loan_id' => $loanId,
            'action' => $action,
            'performed_by' => $userId,
            'notes' => $notes,
            'metadata' => $metadata,
            'performed_at' => now(),
        ]);
    }
}
