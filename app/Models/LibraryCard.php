<?php

namespace App\Models;

use App\Enums\LibraryCardStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LibraryCard extends BaseModel
{
    use SoftDeletes;

    public const HOLDER_TYPE_STUDENT = 'student';

    public const HOLDER_TYPE_TEACHER = 'teacher';

    public const HOLDER_TYPE_EXTERNAL = 'external';

    public const WORKFLOW_DRAFT = 'draft';

    public const WORKFLOW_PENDING_PAYMENT = 'pending_payment';

    public const WORKFLOW_PENDING_REVIEW = 'pending_review';

    public const WORKFLOW_PENDING_PICKUP = 'pending_pickup';

    public const WORKFLOW_ACTIVE = 'active';

    public const WORKFLOW_REJECTED = 'rejected';

    public const WORKFLOW_CANCELLED = 'cancelled';

    public const WORKFLOW_EXPIRED = 'expired';

    public const WORKFLOW_REVOKED = 'revoked';

    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_FAILED = 'failed';

    public const PAYMENT_REFUNDED = 'refunded';

    protected $table = 'library_cards';

    protected $fillable = [
        'user_id',
        'period_id',
        'card_number',
        'holder_type',
        'full_name',
        'email',
        'phone',
        'address',
        'faculty_id',
        'department_id',
        'class_code',
        'date_of_birth',
        'photo_path',
        'external_organization',
        'code',
        'workflow_status',
        'status',
        'issue_date',
        'expiry_date',
        'issued_by',
        'reviewed_by',
        'reviewed_at',
        'notes',
        'params',
        'revoked_at',
        'revoked_reason',
        'payment_status',
        'payment_amount',
        'paid_at',
        'payment_method',
        'receipt_number',
        'payment_collected_by',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => LibraryCardStatus::class,
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'date_of_birth' => 'date',
            'reviewed_at' => 'datetime',
            'revoked_at' => 'datetime',
            'params' => 'array',
        ];
    }

    protected static function boot(): void
    {
        static::saving(function (LibraryCard $card) {
            $fromAttributes = $card->getAttribute('params');
            if (is_array($fromAttributes) && $fromAttributes !== []) {
                $card->arrParams = array_replace_recursive($card->arrParams, $fromAttributes);
            }
        });

        parent::boot();
    }

    protected static function booted(): void
    {
        static::creating(function (LibraryCard $card) {
            if ($card->card_number === null || $card->card_number === '') {
                $card->card_number = filled($card->code)
                    ? $card->code
                    : static::generateCardNumber();
            }
        });
    }

    public static function generateCardNumber(): string
    {
        return DB::transaction(function () {
            do {
                $candidate = 'UTC-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
            } while (static::query()->where('card_number', $candidate)->exists());

            return $candidate;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class, 'faculty_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(LibraryCardPayment::class);
    }
}
