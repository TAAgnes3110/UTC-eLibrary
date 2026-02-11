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

    protected function jsonResponseHtml(string $html, int $code = 200)
    {
        return response($html, $code, ['Content-Type' => 'text/html;charset=UTF-8', 'charset' => 'utf-8']);
    }
}
