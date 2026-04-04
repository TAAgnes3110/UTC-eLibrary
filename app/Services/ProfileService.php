<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    public function getProfilePayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? '',
            'date_of_birth' => $user->date_of_birth?->format('Y-m-d'),
            'gender' => $user->gender,
            'address' => $user->address,
            'avatar' => $this->resolveAvatarUrl($user->avatar),
        ];
    }

    public function updateProfile(User $user, array $validated): array
    {
        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'address' => $validated['address'] ?? null,
        ];

        $user->update($data);

        return $this->getProfilePayload($user->fresh());
    }

    public function updatePassword(User $user, string $newPassword): void
    {
        $user->update(['password' => $newPassword]);
    }

    private function resolveAvatarUrl(?string $avatar): ?string
    {
        if (empty($avatar)) {
            return null;
        }

        if (str_starts_with($avatar, 'http')) {
            return $avatar;
        }

        if (! Storage::disk('public')->exists($avatar)) {
            return null;
        }

        return Storage::url($avatar);
    }
}
