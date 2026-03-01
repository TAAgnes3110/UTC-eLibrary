<?php

namespace App\Http\Controllers\Frontend\Concerns;

use Symfony\Component\HttpFoundation\Response;

/**
 * Decode JSON response từ Backend API (đảm bảo Resource/ResourceCollection đã được serialize).
 */
trait DecodesBackendResponse
{
    protected function decodeBackendResponse(Response $response): array
    {
        $content = $response->getContent();
        if ($content === false || $content === '') {
            return [];
        }
        $body = json_decode($content, true);
        return is_array($body) ? $body : [];
    }

    /** Lấy phần data từ response (success payload). */
    protected function backendData(Response $response): array
    {
        $body = $this->decodeBackendResponse($response);
        return $body['data'] ?? $body;
    }
}
