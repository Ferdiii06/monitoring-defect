<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(\App\Models\CarType::class)) {
            \App\Models\CarType::clearConveyorCache();
        }
    }
}
