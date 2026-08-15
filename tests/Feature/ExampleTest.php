<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_health_endpoint_is_accessible(): void
    {
        $this->getJson('/up')->assertOk();
    }
}
