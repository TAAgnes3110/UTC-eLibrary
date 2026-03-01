<?php

namespace App\Services;

use App\Models\User;

class ProfileService
{
    public function getProfilePayload(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? '',
            'gender' => $user->gender === 'female' ? 'Nữ' : ($user->gender === 'male' ? 'Nam' : 'Khác'),
        ];
    }

    public function updateProfile(User $user, array $validated): array
    {
        $gender = $validated['gender'] ?? $user->gender;
        if ($gender === 'Nữ') {
            $gender = 'female';
        } elseif ($gender === 'Nam') {
            $gender = 'male';
        } elseif ($gender === 'Khác') {
            $gender = 'other';
        }

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'gender' => $gender,
        ];
        if (!empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $user->update($data);

        return $user->only(['name', 'email', 'phone', 'gender']);
    }
}
