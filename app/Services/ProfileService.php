<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    public function getProfilePayload(User $user): array
    {
        $user->loadMissing(['faculty:id,code,name', 'period:id,code,name']);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? '',
            'code' => $user->code,
            'faculty_id' => $user->faculty_id,
            'period_id' => $user->period_id,
            'class_code' => $user->class_code,
            'faculty' => $user->faculty ? [
                'id' => $user->faculty->id,
                'code' => $user->faculty->code,
                'name' => $user->faculty->name,
            ] : null,
            'period' => $user->period ? [
                'id' => $user->period->id,
                'code' => $user->period->code,
                'name' => $user->period->name,
            ] : null,
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

        if (array_key_exists('code', $validated)) {
            $data['code'] = $validated['code'];
        }

        if (array_key_exists('faculty_id', $validated)) {
            $data['faculty_id'] = $validated['faculty_id'];
        }

        if (array_key_exists('class_code', $validated)) {
            $cc = $validated['class_code'] ?? null;
            $data['class_code'] = ($cc !== null && $cc !== '') ? $cc : null;
        }

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

        $disk = (string) config('filesystems.media_disk', 'public');
        try {
            /** @var FilesystemAdapter $storage */
            $storage = Storage::disk($disk);

            return $storage->url($avatar);
        } catch (\Throwable) {
            return null;
        }
    }
}
