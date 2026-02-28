<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends BaseModel
{
    use SoftDeletes;

    protected $table = 'reservations';

    protected $fillable = [
        'user_id',
        'book_id',
        'status',
        'reservation_date',
        'expiry_date',
        'notes',
        'params',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'expiry_date' => 'date',
        'params' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
