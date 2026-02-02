<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailOtp extends BaseModel
{
    public static string $tableName= 'email_otp';
    protected $table='email_otp';
    public $primaryKey='id';
    protected $fillable = [
        'email',
        'otp',
        'expired_at',
    ];
    protected $casts = [
        'expired_at' => 'datetime',
    ];

}
