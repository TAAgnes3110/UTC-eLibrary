<?php

namespace App\Helpers;

use App\Models\User;

class AuthHelper
{
    /**
     * @param User $existingUser
     * @param array $data
     * @return string
     */
    public static function duplicateUserMessage(User $existingUser, array $data): string
    {
        $messages = [];
        if (strcasecmp((string) $existingUser->email, (string) ($data['email'] ?? '')) === 0) {
            $messages[] = __('Email đã tồn tại trong hệ thống.');
        }
        if (strcasecmp((string) $existingUser->code, (string) ($data['code'] ?? '')) === 0) {
            $messages[] = __('Mã số đã tồn tại trong hệ thống.');
        }
        if (!empty($data['phone']) && strcasecmp((string) $existingUser->phone, (string) $data['phone']) === 0) {
            $messages[] = __('Số điện thoại đã tồn tại trong hệ thống.');
        }
        return implode(' ', array_unique($messages)) ?: __('Thông tin đã tồn tại.');
    }
}
