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
  ];

  public function departments(): HasMany
  {
    return $this->hasMany(Department::class);
  }
}
