<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Helpers;
use Tests\TestCase;

class HelpersTest extends TestCase
{
    public function test_filled(): void
    {
        $this->assertFalse(Helpers::filled(null));
        $this->assertFalse(Helpers::filled(''));
        $this->assertFalse(Helpers::filled('   '));
        $this->assertFalse(Helpers::filled(0));
        $this->assertTrue(Helpers::filled(1));
        $this->assertTrue(Helpers::filled('K65-01'));
    }
}
