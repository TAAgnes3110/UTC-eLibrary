<?php

namespace App\Models;

use App\Enums\BookPhysicalCondition;
use App\Enums\BookStatus;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookCopy extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'book_id',
        'barcode',
        'call_number',
        'status',
        'physical_condition',
        'warehouse_id',
        'bookshelf_cell_id',
        'location',
        'params',
    ];

    protected $attributes = [
        'status' => 1,
        'physical_condition' => 'good',
    ];

    protected $casts = [
        'status' => BookStatus::class,
        'physical_condition' => BookPhysicalCondition::class,
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

    public function bookshelfCell()
    {
        return $this->belongsTo(BookshelfCell::class);
    }
}
