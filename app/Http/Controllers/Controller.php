<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

/**
 * Base controller: helper jsonResponse, success, error, jsonResponseHtml.
 *
 * @todo Thống nhất format response (success/error) với Backend API (status/messages).
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Trả về JSON với UTF-8, unescaped unicode.
     *
     * @param mixed $data
     * @param int $code
     * @return JsonResponse
     */
    protected function jsonResponse(mixed $data, int $code = 200): JsonResponse
    {
        return response()->json($data, $code, ['Content-Type' => 'application/json;charset=UTF-8'], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Chuẩn success: { "success": true, "data": ..., "message": ... }
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $code
     * @return JsonResponse
     */
    protected function success(mixed $data = null, ?string $message = null, int $code = 200): JsonResponse
    {
        $body = ['success' => true];
        if ($data !== null) {
            $body['data'] = $data;
        }
        if ($message !== null) {
            $body['message'] = $message;
        }
        return $this->jsonResponse($body, $code);
    }

    /**
     * Chuẩn lỗi: { "success": false, "message": ..., "errors": ... }
     *
     * @param string $message
     * @param int $code
     * @param mixed $errors
     * @return JsonResponse
     */
    protected function error(string $message, int $code = 400, mixed $errors = null): JsonResponse
    {
        $body = ['success' => false, 'message' => $message];
        if ($errors !== null) {
            $body['errors'] = $errors;
        }
        return $this->jsonResponse($body, $code);
    }

    /**
     * Trả về response HTML (Content-Type text/html).
     *
     * @param string $html
     * @param int $code
     * @return \Illuminate\Http\Response
     */
    protected function jsonResponseHtml(string $html, int $code = 200)
    {
        return response($html, $code, ['Content-Type' => 'text/html;charset=UTF-8', 'charset' => 'utf-8']);
    }
}
