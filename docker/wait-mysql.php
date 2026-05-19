<?php

$host = getenv('DB_HOST') ?: 'mysql';
$port = getenv('DB_PORT') ?: '3306';
$database = getenv('DB_DATABASE') ?: 'utc_elibrary';
$user = getenv('DB_USERNAME') ?: 'utc';
$pass = getenv('DB_PASSWORD') ?: 'secret';

$maxAttempts = (int) (getenv('DB_WAIT_ATTEMPTS') ?: 60);
$sleepSeconds = (int) (getenv('DB_WAIT_SLEEP') ?: 2);

for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
    try {
        new PDO(
            "mysql:host={$host};port={$port};dbname={$database}",
            $user,
            $pass,
            [PDO::ATTR_TIMEOUT => 3]
        );
        fwrite(STDOUT, "MySQL sẵn sàng.\n");
        exit(0);
    } catch (Throwable) {
        fwrite(STDOUT, "Đang chờ MySQL ({$attempt}/{$maxAttempts})...\n");
        sleep($sleepSeconds);
    }
}

fwrite(STDERR, "Không kết nối được MySQL.\n");
exit(1);
