<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;


class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;


    protected function jsonResponse(mixed $data, int $code = 200): JsonResponse
    {
        return response()->json($data, $code, ['Content-Type' => 'application/json;charset=UTF-8'], JSON_UNESCAPED_UNICODE);
    }

    protected function success(mixed $data = null, ?string $message = null, int $code = 200): JsonResponse
    {
        $body = [
            'status' => 'success',
            'messages' => $message ?? __('Thành công.'),
        ];
        if ($data !== null) {
            $body['data'] = $data;
        }

        return $this->jsonResponse($body, $code);
    }

    /**
     * Chuẩn lỗi đồng nhất API: { "status": "error", "messages": "...", "errors": ... }
     */
    protected function error(string $message, int $code = 400, mixed $errors = null): JsonResponse
    {
        $body = ['status' => 'error', 'messages' => $message];
        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return $this->jsonResponse($body, $code);
    }

    /**
     * Trả về response HTML (Content-Type text/html).
     *
     * @return Response
     */
    protected function jsonResponseHtml(string $html, int $code = 200)
    {
        return response($html, $code, ['Content-Type' => 'text/html;charset=UTF-8', 'charset' => 'utf-8']);
    }
}
