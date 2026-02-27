<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookAuthor extends Pivot
{
    use SoftDeletes;

    protected $table = 'book_author';

    protected $fillable = ['book_id', 'author_id', 'role', 'order'];

    protected $casts = [
        'order' => 'integer',
    ];
}
