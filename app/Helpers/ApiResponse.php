<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    private const KEY_STATUS = 'status';
    private const KEY_MESSAGES = 'messages';
    private const KEY_DATA = 'data';
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

    /**
     * Response thành công.
     *
     * @param mixed $data Dữ liệu trả về (optional).
     * @param string|null $message Thông báo (dùng key messages trong JSON).
     * @param int $code HTTP status.
     * @return JsonResponse
     */
    public static function success(mixed $data = null, ?string $message = null, int $code = 200): JsonResponse
    {
        $body = [self::KEY_STATUS => self::STATUS_SUCCESS];
        if ($message !== null && $message !== '') {
            $body[self::KEY_MESSAGES] = $message;
        }
        if ($data !== null) {
            $body[self::KEY_DATA] = $data;
        }
        return self::json($body, $code);
    }

    /**
     * Response lỗi.
     *
     * @param string $message Thông báo lỗi.
     * @param int $code HTTP status (mặc định 400).
     * @param mixed $data Optional data (vd. validation errors).
     * @return JsonResponse
     */
    public static function error(string $message, int $code = 400, mixed $data = null): JsonResponse
    {
        $body = [
            self::KEY_STATUS => self::STATUS_ERROR,
            self::KEY_MESSAGES => $message,
        ];
        if ($data !== null) {
            $body[self::KEY_DATA] = $data;
        }
        return self::json($body, $code);
    }

    /**
     * Response 410 Gone (không tìm thấy tài nguyên).
     *
     * @param string|null $message Nếu null thì dùng messages.error_410.
     * @return JsonResponse
     */
    public static function notFound(?string $message = null): JsonResponse
    {
        return self::error(
            $message ?? __('messages.error_410'),
            410,
            []
        );
    }

    /**
     * Trả về JsonResponse với UTF-8, unescaped unicode.
     *
     * @param mixed $data Array hoặc Arrayable/Jsonable (vd. JsonResource).
     * @param int $code HTTP status.
     * @return JsonResponse
     */
    public static function json(mixed $data, int $code = 200): JsonResponse
    {
        return response()->json(
            $data,
            $code,
            ['Content-Type' => 'application/json;charset=UTF-8'],
            JSON_UNESCAPED_UNICODE
        );
    }
}
