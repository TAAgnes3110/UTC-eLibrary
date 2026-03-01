<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use App\Enums\RoleType;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, \Spatie\Permission\Traits\HasRoles;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'code',
        'phone',
        'user_type',
        'avatar',
        'date_of_birth',
        'gender',
        'address',
        'faculty_id',
        'department_id',
        'cohort',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'password' => 'hashed',
            'user_type' => RoleType::class,
            'is_active' => 'boolean',
        ];
    }
    public function faculty(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function department(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function libraryCard(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(LibraryCard::class);
    }

    public function loans(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function fines(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Fine::class);
    }

    public function reservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function scopeDuplicate($query, array $data, ?int $excludeId = null)
    {
        return $query->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->where(function ($q) use ($data) {
                $q->where('email', $data['email'])
                    ->orWhere('code', $data['code'])
                    ->when(!empty($data['phone']), fn($q) => $q->orWhere('phone', $data['phone']));
            });
    }
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getPermissionNames()
        ];
    }
}
