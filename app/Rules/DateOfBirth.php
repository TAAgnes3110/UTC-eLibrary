<?php

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Ngày sinh hợp lệ: trước hôm nay và năm sinh nhỏ hơn năm hiện tại.
 */
class DateOfBirth implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        try {
            $birth = Carbon::parse((string) $value)->startOfDay();
        } catch (\Throwable) {
            $fail('Ngày sinh không hợp lệ.');

            return;
        }

        $today = Carbon::today();

        if ($birth->greaterThanOrEqualTo($today)) {
            $fail('Ngày sinh phải trước ngày hiện tại.');

            return;
        }

        if ($birth->year >= $today->year) {
            $fail('Năm sinh phải nhỏ hơn năm hiện tại.');
        }
    }
}
