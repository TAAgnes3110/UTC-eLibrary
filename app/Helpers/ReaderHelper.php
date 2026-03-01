<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Arr;

class ReaderHelper
{
    /**
     * @param User $u
     * @return array
     */
    public static function mapUserToReaderArray(User $u): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'code' => $u->code,
            'card_number' => $u->libraryCard?->card_number,
            'issue_date' => $u->libraryCard?->issue_date?->format('Y-m-d'),
            'expiry_date' => $u->libraryCard?->expiry_date?->format('Y-m-d'),
            'faculty_id' => $u->faculty_id,
            'faculty' => $u->faculty?->name ?? Arr::get($u->libraryCard?->metadata ?? [], 'faculty'),
            'cohort' => $u->cohort,
            'department_id' => $u->department_id,
            'class' => $u->department?->name ?? Arr::get($u->libraryCard?->metadata ?? [], 'class'),
            'type' => Arr::get($u->libraryCard?->metadata ?? [], 'type') === 'teacher' ? 'teacher' : 'student',
            'status' => $u->is_active ? 'active' : 'blocked',
            'gender' => $u->gender === 'male' ? 'Nam' : ($u->gender === 'female' ? 'Nữ' : 'Khác'),
            'email' => $u->email,
            'phone' => $u->phone,
        ];
    }
}
