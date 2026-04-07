<?php

namespace App\Models;

class LoanPolicy extends BaseModel
{
    protected $fillable = [
        'code',
        'name',
        'user_type',
        'max_books',
        'max_days',
        'max_renewals',
        'overdue_fine_per_day',
        'allow_home',
        'allow_onsite',
        'params',
    ];

    protected $casts = [
        'params' => 'array',
        'max_books' => 'integer',
        'max_days' => 'integer',
        'max_renewals' => 'integer',
        'overdue_fine_per_day' => 'decimal:2',
        'allow_home' => 'boolean',
        'allow_onsite' => 'boolean',
    ];

    /**
     * Policy áp dụng khi user_type khớp enum RoleType, hoặc cột null = mặc định cho mọi đối tượng.
     */
    public function appliesToUser(User $user): bool
    {
        if ($this->user_type === null || $this->user_type === '') {
            return true;
        }

        return strcasecmp((string) $this->user_type, $user->user_type->value) === 0;
    }
}
