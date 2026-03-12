<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'registration_number',
        'book_code',
        'title',
        'sub_title',
        'language',
        'edition',
        'published_year',
        'pages',
        'illustration_pages',
        'book_size',
        'price',
        'quantity',
        'summary',
        'notes',
        'series_name',
        'publisher_place',
        'cabinet',
        'shelf',
        'cover_image',
        'classification_id',
        'classification_detail_id',
        'warehouse_id',
        'params',
    ];

    protected $casts = [
        'params' => 'array',
        'published_year' => 'integer',
        'pages' => 'integer',
        'illustration_pages' => 'integer',
        'quantity' => 'integer',
        'price' => 'integer',
    ];

    public function classification()
    {
        return $this->belongsTo(Classification::class);
    }

    public function classificationDetail()
    {
        return $this->belongsTo(ClassificationDetail::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function authors()
    {
        return $this->belongsToMany(Author::class, 'book_authors')
            ->withTimestamps()
            ->withPivot('order')
            ->orderBy('book_authors.order');
    }

    public function publishers()
    {
        return $this->belongsToMany(Publisher::class, 'book_publishers')
            ->withTimestamps()
            ->withPivot('order')
            ->orderBy('book_publishers.order');
    }

    public function copies()
    {
        return $this->hasMany(BookCopy::class);
    }

    public function availableCopies()
    {
        return $this->hasMany(BookCopy::class)->where('status', 'available');
    }
}

