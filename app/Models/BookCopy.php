<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class BookCopy extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'book_id',
        'barcode',
        'call_number',
        'status',
        'warehouse_id',
        'location',
        'params',
    ];

    protected $casts = [
        'params' => 'array',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }
}

