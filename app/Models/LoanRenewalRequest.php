<?php

namespace App\Models;

use App\Models\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanRenewalRequest extends BaseModel
{
    use HasAuditFields;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'loan_id',
        'requested_by',
        'current_due_date',
        'requested_due_date',
        'status',
        'request_note',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'current_due_date' => 'date',
        'requested_due_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
