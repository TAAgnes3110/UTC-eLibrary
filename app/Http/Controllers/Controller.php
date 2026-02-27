<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function jsonResponse(mixed $data, int $code = 200): JsonResponse
    {
        return response()->json($data, $code, ['Content-Type' => 'application/json;charset=UTF-8'], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Standard success response: { "success": true, "data": ..., "message": ... }
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
     * Standard error response: { "success": false, "message": ..., "errors": ... }
     */
    protected function error(string $message, int $code = 400, mixed $errors = null): JsonResponse
    {
        $body = ['success' => false, 'message' => $message];
        if ($errors !== null) {
            $body['errors'] = $errors;
        }
        return $this->jsonResponse($body, $code);
    }

    protected function jsonResponseHtml(string $html, int $code = 200)
    {
        return response($html, $code, ['Content-Type' => 'text/html;charset=UTF-8', 'charset' => 'utf-8']);
    }
}
