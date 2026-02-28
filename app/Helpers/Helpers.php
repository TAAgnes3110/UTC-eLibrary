<?php

namespace App\Helpers;

/**
 * Helper tiện ích: sinh số ngẫu nhiên (OTP), merge mảng không trùng.
 *
 * @todo generateRandomNumber: dùng random_int() thay rand() cho bảo mật.
 */
class Helpers
{
    /**
     * Generate a random numeric string.
     *
     * @param int $length
     * @param string $prefix
     * @return string
     */
    public static function generateRandomNumber(int $length = 10, string $prefix = ''): string
    {
        $characters       = '0123456789';
        $charactersLength = strlen($characters);
        $randomString     = $prefix;
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * Merge two arrays, avoiding duplicates.
     *
     * @param array|null $arr
     * @param array|null $arr2
     * @return array
     */
    public static function ArrMerge($arr, $arr2): array
    {
        if ($arr) {
            if ($arr2) {
                foreach ($arr2 as $value) {
                    if (!in_array($value, $arr)) {
                        $arr[] = $value;
                    }
                }
            }
        } else {
            return $arr2;
        }
        return $arr;
    }
}
