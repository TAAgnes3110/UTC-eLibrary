<?php

namespace App\Support;

final class ApiAllowedDomains
{
    /**
     * @return list<string>
     */
    public static function resolve(): array
    {
        $fromEnv = array_filter(array_map('trim', explode(',', (string) env('API_ALLOWED_DOMAINS', ''))));

        $fromAppUrl = [];
        $appUrl = (string) env('APP_URL', '');
        if ($appUrl !== '') {
            $host = parse_url($appUrl, PHP_URL_HOST);
            $port = parse_url($appUrl, PHP_URL_PORT);
            if (is_string($host) && $host !== '') {
                $fromAppUrl[] = $host;
                $fromAppUrl[] = $appUrl;
                if ($port) {
                    $fromAppUrl[] = "{$host}:{$port}";
                }
            }
        }

        return array_values(array_unique(array_filter(array_merge($fromEnv, $fromAppUrl))));
    }
}
