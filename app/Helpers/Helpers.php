<?php

namespace App\Helpers;

class Helpers
{
    /**
     * Generate a random numeric string.
     */
    public static function generateRandomNumber(int $length = 10, string $prefix = ''): string
    {
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = $prefix;
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    /**
     * Merge two arrays, avoiding duplicates.
     *
     * @param  array|null  $arr
     * @param  array|null  $arr2
     */
    public static function ArrMerge($arr, $arr2): array
    {
        if ($arr) {
            if ($arr2) {
                foreach ($arr2 as $value) {
                    if (! in_array($value, $arr)) {
                        $arr[] = $value;
                    }
                }
            }
        } else {
            return $arr2;
        }

        return $arr;
    }

    /**
     * Giá trị có thể coi là đã nhập: null, chuỗi rỗng/khoảng trắng, số ≤ 0 → false; còn lại → true.
     */
    public static function filled(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }
        if (is_string($value)) {
            return trim($value) !== '';
        }
        if (is_int($value) || is_float($value)) {
            return (int) $value > 0;
        }

        return true;
    }
}
