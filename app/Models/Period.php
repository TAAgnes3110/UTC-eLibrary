<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Period extends BaseModel
{
    protected $table = 'periods';
    public static string $tableName = 'periods';
    public $primaryKey = 'id';

    protected $fillable = [
        'name',
        'code',
        'start_date',
        'end_date',
        'status',
    ];
}
