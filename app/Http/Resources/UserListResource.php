<?php

namespace App\Http\Resources;

use App\Helpers\FileHelpers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $avatar = $this->avatar;
        $mediaDisk = (string) config('filesystems.media_disk', 'public');
        /** @var \Illuminate\Filesystem\FilesystemAdapter $mediaStorage */
        $mediaStorage = Storage::disk($mediaDisk);
        if (! empty($avatar) && ! str_starts_with((string) $avatar, 'http')) {
            $avatar = $mediaStorage->url((string) $avatar);
        }

        $userType = $this->user_type instanceof \BackedEnum ? $this->user_type->value : $this->user_type;
        $status = 'active';
        if ($this->trashed()) {
            $status = 'inactive';
        } elseif (! $this->is_active) {
            $status = 'blocked';
        }

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'user_type' => $userType,
            'role' => $userType,
            'status' => $status,
            'avatar' => $avatar ?: FileHelpers::mediaDefaultUrl('avatar'),
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

