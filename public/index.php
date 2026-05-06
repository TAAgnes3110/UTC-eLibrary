<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Luôn in lỗi PHP/Laravel chi tiết (file + dòng + stack). Rủi ro khi production
| go-live — nên chỉnh lại hoặc dựa vào APP_DEBUG trong .env khi đã ổn định.
|--------------------------------------------------------------------------
*/

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

$utcPrintThrowableAndExit = static function (Throwable $e): never {
    if (! headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
    }

    exit(
        "UTC-eLibrary — chi tiết lỗi (public/index.php)\n\n".
        'Loại exception: '.$e::class."\n".
        'Nội dung lỗi: '.$e->getMessage()."\n\n".
        "► Vị trí gốc của lỗi:\n".
        '   File: '.$e->getFile()."\n".
        '   Dòng: '.$e->getLine()."\n\n".
        "▼ Stack trace:\n".$e->getTraceAsString()."\n"
    );
};

register_shutdown_function(static function (): void {
    $last = error_get_last();
    if ($last === null) {
        return;
    }

    $fatalLevels = [
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_COMPILE_ERROR,
        E_USER_ERROR,
    ];

    if (! in_array($last['type'], $fatalLevels, true)) {
        return;
    }

    if (! headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
    }

    $typeNames = [
        E_ERROR => 'E_ERROR',
        E_PARSE => 'E_PARSE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_USER_ERROR => 'E_USER_ERROR',
    ];

    $typeLabel = $typeNames[$last['type']] ?? (string) $last['type'];

    echo "UTC-eLibrary — chi tiết lỗi (public/index.php)\n\n";
    echo 'Loại PHP: '.$typeLabel."\n";
    echo 'Thông báo: '.$last['message']."\n\n";
    echo "► Vị trí làm PHP dừng:\n";
    echo '   File: '.$last['file']."\n";
    echo '   Dòng: '.$last['line']."\n";

    exit(1);
});

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    try {
        require $maintenance;
    } catch (Throwable $e) {
        $utcPrintThrowableAndExit($e);
    }
}

// Register the Composer autoloader...
try {
    require __DIR__.'/../vendor/autoload.php';
} catch (Throwable $e) {
    $utcPrintThrowableAndExit($e);
}

// Bootstrap Laravel and handle the request...
/** @var Application $app */
try {
    $app = require_once __DIR__.'/../bootstrap/app.php';
} catch (Throwable $e) {
    $utcPrintThrowableAndExit($e);
}

try {
    $app->handleRequest(Request::capture());
} catch (Throwable $e) {
    $utcPrintThrowableAndExit($e);
}
