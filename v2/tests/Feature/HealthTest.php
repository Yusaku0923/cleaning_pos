<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_up_returns_200(): void
    {
        $this->get('/up')->assertOk();
    }
}
