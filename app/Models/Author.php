<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Author extends BaseModel
{
  protected $fillable = [
    'name',
    'pen_name',
    'biography',
    'nationality',
    'birth_date',
    'death_date',
    'email',
    'website',
    'avatar',
    'params',
  ];

  protected $casts = [
    'birth_date' => 'date',
    'death_date' => 'date',
    'params' => 'array',
  ];

  public function books(): BelongsToMany
  {
    return $this->belongsToMany(Book::class, 'book_author')
      ->withPivot('role', 'order')
      ->withTimestamps();
  }
}
