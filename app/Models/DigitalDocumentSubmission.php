<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalDocumentSubmission extends BaseModel
{
    protected static bool $persistParamsToDatabase = false;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'submitted_by',
        'title',
        'author_names',
        'description',
        'file_path',
        'original_name',
        'mime',
        'byte_size',
        'cover_image_path',
        'status',
        'review_note',
        'reviewed_by',
        'reviewed_at',
        'approved_book_id',
        'user_hidden_at',
    ];

    protected $casts = [
        'byte_size' => 'integer',
        'reviewed_at' => 'datetime',
        'user_hidden_at' => 'datetime',
    ];

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedBook(): BelongsTo
    {
        return $this->belongsTo(Book::class, 'approved_book_id');
    }
}
