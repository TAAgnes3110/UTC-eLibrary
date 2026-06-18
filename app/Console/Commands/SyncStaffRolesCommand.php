<?php

namespace App\Console\Commands;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SyncStaffRolesCommand extends Command
{
    protected $signature = 'library:sync-staff-roles {--dry-run : Chỉ liệt kê, không ghi DB}';

    protected $description = 'Đồng bộ Spatie role (guard api) cho tài khoản staff theo user_type — sửa lỗi đăng nhập admin khi pivot role lỗi/thiếu.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $guard = 'api';
        $synced = 0;

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        User::query()
            ->whereIn('user_type', RoleType::staffRoles())
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($guard, $dryRun, &$synced): void {
                foreach ($users as $user) {
                    $roleName = $user->user_type instanceof RoleType
                        ? $user->user_type->value
                        : (string) $user->user_type;

                    if ($roleName === '') {
                        $this->warn("User #{$user->id}: bỏ qua — user_type rỗng.");

                        continue;
                    }

                    if ($dryRun) {
                        $this->line("User #{$user->id} ({$user->email}): sẽ gán role {$roleName}");
                        $synced++;

                        continue;
                    }

                    Role::firstOrCreate(
                        ['name' => $roleName, 'guard_name' => $guard],
                        ['name' => $roleName, 'guard_name' => $guard]
                    );

                    $user->syncRoles([$roleName]);
                    $synced++;
                    $this->info("User #{$user->id} ({$user->email}): đã sync role {$roleName}");
                }
            });

        if (! $dryRun) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        $this->info($dryRun
            ? "Dry-run: {$synced} tài khoản staff cần sync."
            : "Hoàn tất: {$synced} tài khoản staff đã sync role.");

        return self::SUCCESS;
    }
}
