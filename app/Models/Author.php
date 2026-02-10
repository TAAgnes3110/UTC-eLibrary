<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Author extends BaseModel
{
  protected $fillable = [
    'id',
    'name',
    'tieu_su',
    'birth_date',
    'avatar',
    'params'
  ];

  protected $casts = [
    'birth_date' => 'date',
    'params' => 'array',
  ];

  public function books(): BelongsToMany
  {
    return $this->belongsToMany(Book::class, 'book_author')
      ->withPivot('role', 'order')
      ->withTimestamps();
  }
}
