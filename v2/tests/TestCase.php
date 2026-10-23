<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // テストでは Vite のビルド結果 (public/build) を要求しない
        $this->withoutVite();
    }
}
