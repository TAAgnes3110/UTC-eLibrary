<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupStandardDatabaseCommand extends Command
{
    protected $signature = 'db:setup-standard
                            {--demo : Nạp thêm dữ liệu demo (sách/thẻ mẫu)}';

    protected $description = 'Khởi tạo lại database chuẩn (migrate:fresh + seed nền tảng)';

    public function handle(): int
    {
        $withDemo = (bool) $this->option('demo');

        $this->components->info('Bắt đầu dựng bộ database chuẩn...');

        // Chỉ bật seed demo theo cờ --demo để môi trường mặc định luôn gọn và ổn định.
        config(['app.seed_demo_data' => $withDemo]);
        putenv('SEED_DEMO_DATA='.($withDemo ? 'true' : 'false'));
        $_ENV['SEED_DEMO_DATA'] = $withDemo ? 'true' : 'false';
        $_SERVER['SEED_DEMO_DATA'] = $withDemo ? 'true' : 'false';

        $exitCode = $this->call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]);

        if ($exitCode !== self::SUCCESS) {
            $this->components->error('Không thể khởi tạo database chuẩn.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Đã hoàn tất database chuẩn.');
        $this->line($withDemo
            ? '- Đã nạp dữ liệu nền tảng + dữ liệu demo.'
            : '- Đã nạp dữ liệu nền tảng (không gồm demo).');
        $this->line('- Tài khoản mặc định: xem trong DefaultUsersSeeder.');
        $this->line('- Chạy lại với --demo nếu cần dữ liệu mẫu cho UI.');

        return self::SUCCESS;
    }
}
