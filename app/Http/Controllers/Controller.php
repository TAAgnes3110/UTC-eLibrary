<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Trả về JSON response chuẩn cho client.
     * TODO: Kiểm tra lại logic biến global $__token, cân nhắc chuyển sang Middleware để xử lý token sạch hơn.
     */
    protected function jsonResponse(mixed $data, int $code = 200): JsonResponse
    {
        global $__token;
        if (!empty($__token) && is_array($data)) {
            $data['__token'] = $__token;
        }
        return response()->json($data, $code, ['Content-Type' => 'application/json;charset=UTF-8'], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Trả về HTML response chuẩn.
     */
    protected function jsonResponseHtml(string $html, int $code = 200)
    {
        return response($html, $code, ['Content-Type' => 'text/html;charset=UTF-8', 'charset' => 'utf-8']);
    }

    /**
     * Thiết lập header cho các request gọi đi (Authorization, Domain...).
     * TODO: Refactor để loại bỏ phụ thuộc vào biến global ($bearer_token, $domain...), nên inject qua Service hoặc Config.
     */
    protected function setHeader(): array
    {
        global $bearer_token, $domain, $period, $yaht;
        return [
            'Authorization' => 'Bearer ' . $bearer_token,
            'domain' => $domain,
            'period' => $period,
            'yaht' => $yaht,
        ];
    }

    /**
     * Helper để gọi API sang các service khác.
     * TODO: Bổ sung xử lý Exception (Timeout, Connection Error) để tránh crash ứng dụng khi service đích chết.
     */
    protected function callApi(string $url, string $method = 'get', array $data = []): array
    {
        $method = strtolower($method);
        $http = Http::withOptions(['verify' => false])->withHeaders($this->setHeader());

        $response = match ($method) {
            'post' => $http->post($url, $data),
            'put' => $http->put($url, $data),
            'patch' => $http->patch($url, $data),
            'delete' => $http->delete($url, $data),
            default => $http->get($url, $data),
        };

        if ($response->ok()) {
            return $response->json() ?? [];
        }
        return [];
    }

    /**
     * Xử lý dữ liệu trả về từ API (convert sang Collection hoặc lấy mảng key-value).
     */
    protected function processResponse(?array $res, bool $asCollection, ?string $pluckValue = null, ?string $pluckKey = null): array|Collection
    {
        if (empty($res['data'])) {
            return [];
        }

        if ($asCollection) {
            return collect($res['data']);
        }

        if ($pluckValue && $pluckKey) {
            return collect($res['data'])->pluck($pluckValue, $pluckKey)->toArray();
        }

        return $res['data'];
    }

    /**
     * Lấy danh sách Persons từ service User/Base.
     * TODO: Cần kiểm tra lại $apis['base'] có tồn tại không trước khi nối chuỗi.
     */
    protected function getPersons(string $subUrl = 'single', array $data = [], bool $return_collect = false, string $method = 'put'): array|Collection
    {
        global $apis;
        $res = $this->callApi($apis['base'] . 'persons/' . $subUrl, $method, $data);
        return $this->processResponse($res, $return_collect, 'name', 'id');
    }
}
