<?php

namespace Tests\Feature\Backend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MigrateExistingSchemaCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_succeeds_when_all_migrations_already_applied(): void
    {
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();

        $this->artisan('migrate:existing-schema', ['--force' => true])
            ->expectsOutputToContain('Không có migration pending')
            ->assertSuccessful();
    }
}
