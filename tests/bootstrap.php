<?php

/**
 * Bootstrap cho PHPUnit.
 * Nếu không có PDO SQLite → dùng MySQL, tự tạo DB test nếu chưa có.
 *
 * @see phpunit.xml
 * @see tests/README.md
 */
if (! extension_loaded('pdo_sqlite')) {
    putenv('DB_CONNECTION=mysql');
    $_ENV['DB_CONNECTION'] = 'mysql';

    $envFile = dirname(__DIR__) . '/.env';
    $vars = ['DB_DATABASE' => 'elibrary', 'DB_HOST' => '127.0.0.1', 'DB_PORT' => '3306', 'DB_USERNAME' => 'root', 'DB_PASSWORD' => ''];
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $parts = explode('=', $line, 2);
            if (count($parts) < 2) {
                continue;
            }
            $key = trim($parts[0]);
            $val = trim($parts[1], " \t\n\r\0\x0B\"'");
            if (isset($vars[$key]) || in_array($key, ['DB_DATABASE', 'DB_HOST', 'DB_PORT', 'DB_USERNAME', 'DB_PASSWORD'])) {
                $vars[$key] = $val;
            }
        }
    }

    $testDb = ($vars['DB_DATABASE'] ?? 'elibrary') . '_test';
    if ($testDb === '_test') {
        $testDb = 'elibrary_test';
    }
    putenv('DB_DATABASE=' . $testDb);
    $_ENV['DB_DATABASE'] = $testDb;

    // Tự tạo DB test nếu chưa có
    try {
        $dsn = sprintf(
            'mysql:host=%s;port=%s',
            $vars['DB_HOST'] ?? '127.0.0.1',
            $vars['DB_PORT'] ?? '3306'
        );
        $pdo = new PDO(
            $dsn,
            $vars['DB_USERNAME'] ?? 'root',
            $vars['DB_PASSWORD'] ?? ''
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $name = str_replace(['`', "\0"], ['``', ''], $testDb);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}`");
    } catch (PDOException $e) {
        // Bỏ qua nếu không tạo được (user sẽ tạo thủ công)
    }
}

require __DIR__ . '/../vendor/autoload.php';
