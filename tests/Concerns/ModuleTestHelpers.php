<?php

namespace Tests\Concerns;

use App\Models\User;
use Tests\Feature\Backend\ActsAsApiUser;

/**
 * Helper dùng chung cho bộ test theo module (~10 case/module).
 */
trait ModuleTestHelpers
{
    use ActsAsApiUser;

    protected function bearer(string $token): array
    {
        return $this->apiTokenHeaders($token);
    }

    /** @return array{0: User, 1: array<string, string>} */
    protected function studentContext(): array
    {
        [$user, $token] = $this->createUserAndToken();

        return [$user, $this->bearer($token)];
    }

    /** @return array{0: User, 1: array<string, string>} */
    protected function adminContext(): array
    {
        [$user, $token] = $this->createAdminUserAndToken();

        return [$user, $this->bearer($token)];
    }

    /** @return array{0: User, 1: array<string, string>} */
    protected function librarianContext(): array
    {
        [$user, $token] = $this->createLibrarianUserAndToken();

        return [$user, $this->bearer($token)];
    }
}
