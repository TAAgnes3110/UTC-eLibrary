<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends BaseModel
{
    use SoftDeletes;

    public static string $tableName = 'books';
    protected $table = 'books';

    protected $fillable = [
        'title',
        'isbn',
        'isbn13',
        'classification_code',
        'classification_detail',
        'category_id',
        'publisher_id',
        'publication_place',
        'published_year',
        'edition',
        'series',
        'total_pages',
        'book_size',
        'dimensions',
        'weight',
        'volume_number',
        'language',
        'price',
        'notes',
        'status',
        'total_copies',
        'available_copies',
        'loan_count',
        'reservation_count',
        'average_rating',
        'keywords',
        'summary',
        'cover_image',
        'thumbnail',
        'params',
    ];

    protected $casts = [
        'params' => 'array',
        'price' => 'decimal:2',
        'average_rating' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'book_author')
            ->withPivot('role', 'order')
            ->orderBy('order')
            ->withTimestamps();
    }

    public function copies(): HasMany
    {
        return $this->hasMany(BookCopy::class);
    }

    public function availableCopies(): HasMany
    {
        return $this->hasMany(BookCopy::class)->where('status', 'available');
    }

    public function updateStatistics(): void
    {
        $this->total_copies = $this->copies()->count();
        $this->available_copies = $this->availableCopies()->count();
        $this->save();
    }
}
