<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContactMessage extends BaseModel
{
    use HasFactory;

    protected $table = 'contact_messages';

    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'status',
        'handled_by',
        'handled_at',
        'params',
    ];

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
            'params' => 'array',
        ];
    }

    public function handler(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}

