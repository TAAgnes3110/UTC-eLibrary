<?php

namespace App\Models;

use App\Models\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfileUpdateRequest extends BaseModel
{
    use HasAuditFields;

    protected static bool $persistParamsToDatabase = false;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $table = 'user_profile_update_requests';

    protected $fillable = [
        'user_id',
        'requested_code',
        'requested_user_type',
        'requested_faculty_id',
        'requested_period_id',
        'requested_class_code',
        'proof_image_path',
        'status',
        'is_visible',
        'reason',
        'review_note',
        'reviewed_by',
        'reviewed_at',
        'applied_at',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'reviewed_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function requestedFaculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class, 'requested_faculty_id');
    }

    public function requestedPeriod(): BelongsTo
    {
        return $this->belongsTo(Period::class, 'requested_period_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

