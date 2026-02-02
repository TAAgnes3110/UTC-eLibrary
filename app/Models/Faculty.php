<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faculty extends BaseModel
{
  use SoftDeletes;

  protected $fillable = [
    'code',
    'name',
    'description',
  ];

  public function departments(): HasMany
  {
    return $this->hasMany(Department::class);
  }

  public function readers(): HasMany
  {
    return $this->hasMany(Reader::class);
  }
}
