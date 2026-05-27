<?php

/**
 * Sinh Postman Collection v2.1 từ `php artisan route:list --path=api --json`.
 * Chạy: php scripts/generate-postman-collection.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

$json = shell_exec('php artisan route:list --path=api --json 2>&1');
if (! is_string($json) || $json === '') {
    fwrite(STDERR, "Không lấy được route list.\n");
    exit(1);
}

/** @var list<array{method:string,uri:string,middleware:list<string>}> $routes */
$routes = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

$collection = [
    'info' => [
        'name' => 'UTC-eLibrary API',
        'description' => "Collection tự sinh từ routes Laravel.\n\n"
            . "**Chuẩn bị:**\n"
            . "1. Chạy `POST /api/v1/auth/login` (folder Auth) — token lưu vào biến `token`.\n"
            . "2. Header `domain` = `{{DOMAIN}}` (mặc định trùng BASE_URL).\n"
            . "3. API cần đăng nhập: middleware `init` — gửi `Authorization: Bearer {{token}}`.\n"
            . "4. Admin Inertia dùng cookie session; collection này kiểm thử **API JWT**.\n"
            . "5. Demo EC2: đổi `BASE_URL` = `{{BASE_URL_PROD}}` hoặc `http://kiet.mmoall.com`.\n",
        '_postman_id' => '7b3a2c31-9c64-4e6c-b3c0-6e6d4c3b2b10',
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => [
        ['key' => 'BASE_URL', 'value' => 'http://localhost:8000'],
        ['key' => 'DOMAIN', 'value' => 'http://localhost:8000'],
        ['key' => 'BASE_URL_PROD', 'value' => 'http://kiet.mmoall.com'],
        ['key' => 'token', 'value' => ''],
        ['key' => 'book_id', 'value' => '1'],
        ['key' => 'loan_id', 'value' => '1'],
        ['key' => 'library_card_id', 'value' => '1'],
        ['key' => 'user_id', 'value' => '1'],
        ['key' => 'digital_asset_id', 'value' => '1'],
        ['key' => 'submission_id', 'value' => '1'],
        ['key' => 'notification_id', 'value' => '1'],
        ['key' => 'order_public_id', 'value' => ''],
    ],
    'event' => [
        [
            'listen' => 'prerequest',
            'script' => [
                'type' => 'text/javascript',
                'exec' => [
                    "pm.request.headers.upsert({ key: 'domain', value: pm.variables.get('DOMAIN') || pm.variables.get('BASE_URL') });",
                    "pm.request.headers.upsert({ key: 'Accept', value: 'application/json' });",
                    "const token = pm.variables.get('token');",
                    "if (token && String(token).trim()) {",
                    "  pm.request.headers.upsert({ key: 'Authorization', value: 'Bearer ' + token });",
                    "} else {",
                    "  pm.request.headers.remove('Authorization');",
                    "}",
                ],
            ],
        ],
    ],
    'item' => [],
];

/** @var array<string, list<array<string,mixed>>> $folders */
$folders = [];

foreach ($routes as $route) {
    $methods = explode('|', (string) ($route['method'] ?? 'GET'));
    $uri = (string) ($route['uri'] ?? '');
    if ($uri === '') {
        continue;
    }

    $folderKey = folderKeyForUri($uri);
    if (! isset($folders[$folderKey])) {
        $folders[$folderKey] = [];
    }

    foreach ($methods as $method) {
        $method = strtoupper(trim($method));
        if ($method === 'HEAD') {
            continue;
        }
        $folders[$folderKey][] = buildRequestItem($method, $uri);
    }
}

ksort($folders);

foreach ($folders as $name => $items) {
    usort($items, static fn ($a, $b) => strcmp($a['name'], $b['name']));
    $collection['item'][] = [
        'name' => $name,
        'item' => $items,
    ];
}

// Auth login: lưu token
foreach ($collection['item'] as &$folder) {
    if ($folder['name'] !== '00 — Auth') {
        continue;
    }
    foreach ($folder['item'] as &$req) {
        if ($req['name'] === 'POST api/v1/auth/login') {
            $req['event'] = [[
                'listen' => 'test',
                'script' => [
                    'type' => 'text/javascript',
                    'exec' => [
                        "const data = pm.response.json();",
                        "const token = data?.data?.access_token || data?.access_token || data?.data?.token || data?.token;",
                        "if (token) { pm.collectionVariables.set('token', token); pm.test('Lưu token', () => pm.expect(token).to.be.a('string')); }",
                    ],
                ],
            ]];
            $req['request']['body'] = [
                'mode' => 'raw',
                'raw' => json_encode([
                    'login' => 'admin@utc.edu.vn',
                    'password' => 'password',
                    'remember' => false,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'options' => ['raw' => ['language' => 'json']],
            ];
            $req['request']['header'] = [
                ['key' => 'Content-Type', 'value' => 'application/json'],
            ];
        }
    }
}
unset($folder, $req);

$out = $root.'/UTC-eLibrary.postman_collection.json';
file_put_contents($out, json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
echo "Wrote {$out} (".count($routes)." routes, ".count($folders)." folders)\n";

function folderKeyForUri(string $uri): string
{
    if ($uri === 'api/health') {
        return '00 — Health';
    }
    if (preg_match('#^api/v1/auth(?:/|$)#', $uri)) {
        return '00 — Auth';
    }
    if (str_starts_with($uri, 'api/v1/me/')) {
        return '01 — Me (độc giả)';
    }
    if (str_starts_with($uri, 'api/v1/me')) {
        return '01 — Me (độc giả)';
    }
    if (str_contains($uri, 'digital-document-submissions') && ! str_starts_with($uri, 'api/v1/me')) {
        return '02 — Digital submissions (staff)';
    }
    if (str_starts_with($uri, 'api/v1/news-posts')) {
        return '03 — News (public + staff)';
    }
    if (str_starts_with($uri, 'api/v1/sepay')) {
        return '04 — Webhooks';
    }
    if (str_starts_with($uri, 'api/v1/library-cards/guest')) {
        return '05 — Public / guest';
    }
    if (str_starts_with($uri, 'api/v1/master-data')) {
        return '05 — Public / guest';
    }
    if (preg_match('#^api/v1/([^/]+)#', $uri, $m)) {
        $segment = $m[1];

        return '10 — Staff / '.ucfirst(str_replace('-', ' ', $segment));
    }

    return '99 — Other';
}

/**
 * @return array<string, mixed>
 */
function buildRequestItem(string $method, string $uri): array
{
    $path = explode('/', $uri);
    $urlPath = array_map(static function (string $part): string {
        return match ($part) {
            '{book}' => '{{book_id}}',
            '{id}' => '{{book_id}}',
            '{loan}' => '{{loan_id}}',
            '{library_card}' => '{{library_card_id}}',
            '{digital_asset}' => '{{digital_asset_id}}',
            '{user}' => '{{user_id}}',
            '{publicId}' => '{{order_public_id}}',
            '{notificationId}' => '{{notification_id}}',
            default => $part,
        };
    }, $path);

    $name = "{$method} {$uri}";
    $item = [
        'name' => $name,
        'request' => [
            'method' => $method,
            'header' => [],
            'url' => [
                'raw' => '{{BASE_URL}}/'.$uri,
                'host' => ['{{BASE_URL}}'],
                'path' => $urlPath,
            ],
        ],
        'response' => [],
    ];

    if (in_array($method, ['POST', 'PUT', 'PATCH'], true) && ! isMultipartUri($uri)) {
        $item['request']['header'][] = ['key' => 'Content-Type', 'value' => 'application/json'];
        $item['request']['body'] = [
            'mode' => 'raw',
            'raw' => sampleBodyForUri($method, $uri),
            'options' => ['raw' => ['language' => 'json']],
        ];
    }

    if (isMultipartUri($uri)) {
        $item['request']['body'] = [
            'mode' => 'formdata',
            'formdata' => multipartFieldsForUri($uri),
        ];
    }

    return $item;
}

function isMultipartUri(string $uri): bool
{
    return str_contains($uri, '/digital')
        || str_contains($uri, '/image')
        || str_contains($uri, '/avatar')
        || str_contains($uri, '/import')
        || str_contains($uri, '/photo')
        || str_contains($uri, 'digital-assets')
        || str_contains($uri, 'digital-document-submissions')
        && str_contains($uri, '/me/');
}

/**
 * @return list<array{key:string,value:string,type:string,description?:string}>
 */
function multipartFieldsForUri(string $uri): array
{
    if (str_ends_with($uri, '/books/digital') || preg_match('#books/\{book\}/digital$#', $uri)) {
        return [
            ['key' => 'title', 'value' => 'Đồ án kiểm thử Postman', 'type' => 'text'],
            ['key' => 'resource_type', 'value' => 'digital', 'type' => 'text'],
            ['key' => 'file', 'type' => 'file', 'description' => 'PDF'],
            ['key' => 'is_primary', 'value' => '1', 'type' => 'text'],
            ['key' => 'visibility', 'value' => 'public', 'type' => 'text'],
        ];
    }
    if (str_contains($uri, 'digital-assets')) {
        return [
            ['key' => 'file', 'type' => 'file'],
            ['key' => 'is_primary', 'value' => '1', 'type' => 'text'],
            ['key' => 'visibility', 'value' => 'public', 'type' => 'text'],
        ];
    }
    if (str_contains($uri, '/me/digital-document-submissions')) {
        return [
            ['key' => 'title', 'value' => 'Luận văn nộp thử', 'type' => 'text'],
            ['key' => 'file', 'type' => 'file', 'description' => 'PDF'],
        ];
    }

    return [['key' => 'file', 'type' => 'file']];
}

function sampleBodyForUri(string $method, string $uri): string
{
    $samples = [
        'api/v1/auth/register' => ['name' => 'Sinh viên Test', 'email' => 'test@st.utc.edu.vn', 'password' => 'password', 'password_confirmation' => 'password'],
        'api/v1/auth/verify-otp' => ['email' => 'test@st.utc.edu.vn', 'otp' => '000000'],
        'api/v1/auth/reset-password' => ['email' => 'test@st.utc.edu.vn', 'otp' => '000000', 'password' => 'password', 'password_confirmation' => 'password'],
        'api/v1/me/loan-borrow-requests' => ['book_copy_ids' => [1]],
        'api/v1/me/digital-purchase-cart/items' => ['digital_asset_id' => 1],
        'api/v1/loans' => ['library_card_id' => 1, 'book_copy_ids' => [1]],
        'api/v1/books' => ['title' => 'Sách thử', 'resource_type' => 'reference', 'warehouse_id' => 1, 'classification_id' => 1, 'quantity' => 1],
    ];

    foreach ($samples as $key => $body) {
        if (str_contains($uri, $key) || $uri === $key) {
            return json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        return "{}";
    }

    return "{}";
}
