<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends BaseModel
{
  use SoftDeletes;

  protected $fillable = [
    'code',
    'name',
    'faculty_id',
  ];

  public function faculty()
  {
    return $this->belongsTo(Faculty::class);
  }
}
