<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_login_page_is_accessible(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
    }

    public function test_the_root_redirects_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
