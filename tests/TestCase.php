<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        if (getenv('DB_DATABASE') !== 'trade_core_test') {
            throw new \RuntimeException(
                'Tests must run against trade_core_test database.',
            );
        }

        parent::setUp();
    }
}
